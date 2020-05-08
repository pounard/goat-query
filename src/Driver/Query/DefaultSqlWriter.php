<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

use Goat\Query\DeleteQuery;
use Goat\Query\Expression;
use Goat\Query\ExpressionColumn;
use Goat\Query\ExpressionConstantTable;
use Goat\Query\ExpressionLike;
use Goat\Query\ExpressionRaw;
use Goat\Query\ExpressionRelation;
use Goat\Query\ExpressionValue;
use Goat\Query\InsertQuery;
use Goat\Query\MergeQuery;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use Goat\Query\Statement;
use Goat\Query\UpdateQuery;
use Goat\Query\Where;
use Goat\Query\Partial\Column;
use Goat\Query\Partial\Join;

/**
 * Standard SQL query formatter: this implementation conforms as much as it
 * can to SQL-92 standard, and higher revisions for some functions.
 *
 * Here are a few differences with the SQL standard:
 *
 *  - per default, UPDATE queries allow FROM..JOIN statement, but first JOIN
 *    must be INNER or NATURAL in order to substitute the JOIN per FROM;
 *
 *  - per default, DELETE queries allow USING..JOIN statement, but first JOIN
 *    must be INNER or NATURAL in order to substitute the JOIN per USING.
 *
 * It will work gracefully with PostgreSQL, but also, from the various
 * documentation I could read, probably with MSSQL too.
 */
class DefaultSqlWriter extends AbstractSqlWriter
{
    /**
     * Format a single set clause (update queries).
     *
     * @param string $columnName
     * @param string|Expression $expression
     */
    protected function formatUpdateSetItem(string $columnName, $expression): string
    {
        $columnString = $this->escaper->escapeIdentifier($columnName);

        if ($expression instanceof Expression) {
            return \sprintf("%s = %s", $columnString, $this->format($expression));
        } else if ($expression instanceof Statement) {
            return \sprintf("%s = (%s)", $columnString, $this->format($expression));
        } else {
            return \sprintf("%s = %s", $columnString, $this->escaper->escapeLiteral($expression));
        }
    }

    /**
     * Format all set clauses (update queries).
     *
     * @param string[]|Expression[] $columns
     *   Keys are column names, values are strings or Expression instances
     */
    protected function formatUpdateSet(array $columns): string
    {
        $output = [];

        foreach ($columns as $column => $statement) {
            $output[] = $this->formatUpdateSetItem($column, $statement);
        }

        return \implode(",\n", $output);
    }

    /**
     * Format projection for a single select column or statement.
     */
    protected function formatSelectItem(Column $column): string
    {
        $output = $this->format($column->expression);

        // We cannot alias columns with a numeric identifier;
        // aliasing with the same string as the column name
        // makes no sense either.
        $alias = $column->alias;
        if ($alias && !\is_numeric($alias)) {
            $alias = $this->escaper->escapeIdentifier($alias);
            if ($alias !== $output) {
                return $output . ' as ' . $alias;
            }
        }

        return $output;
    }

    /**
     * Format the whole projection.
     *
     * @param array $columns
     *   Each column is an array that must contain:
     *     - 0: string or Statement: column name or SQL statement
     *     - 1: column alias, can be empty or null for no aliasing
     */
    protected function formatSelect(array $columns): string
    {
        if (!$columns) {
            return '*';
        }

        $output = [];

        foreach ($columns as $column) {
            $output[] = $this->formatSelectItem($column);
        }

        return \implode(",\n", $output);
    }

    /**
     * Format projection for a single returning column or statement.
     */
    protected function formatReturningItem(Column $column): string
    {
        return $this->formatSelectItem($column->expression, $column->alias);
    }

    /**
     * Format the whole projection.
     *
     * @param array $return
     *   Each column is an array that must contain:
     *     - 0: string or Statement: column name or SQL statement
     *     - 1: column alias, can be empty or null for no aliasing
     */
    protected function formatReturning(array $return): string
    {
        return $this->formatSelect($return);
    }

    /**
     * Format a single order by.
     *
     * @param string|Expression $column
     * @param int $order
     *   Query::ORDER_* constant
     * @param int $null
     *   Query::NULL_* constant
     */
    protected function formatOrderByItem($column, int $order, int $null): string
    {
        $column = $this->format($column);

        if (Query::ORDER_ASC === $order) {
            $orderStr = 'asc';
        } else {
            $orderStr = 'desc';
        }

        switch ($null) {

            case Query::NULL_FIRST:
                $nullStr = ' nulls first';
                break;

            case Query::NULL_LAST:
                $nullStr = ' nulls last';
                break;

            case Query::NULL_IGNORE:
            default:
                $nullStr = '';
                break;
        }

        return \sprintf('%s %s%s', $column, $orderStr, $nullStr);
    }

    /**
     * Format the whole order by clause.
     *
     * @param array $orders
     *   Each order is an array that must contain:
     *     - 0: Expression
     *     - 1: Query::ORDER_* constant
     *     - 2: Query::NULL_* constant
     */
    protected function formatOrderBy(array $orders): string
    {
        if (!$orders) {
            return '';
        }

        $output = [];

        foreach ($orders as $data) {
            list($column, $order, $null) = $data;
            $output[] = $this->formatOrderByItem($column, $order, $null);
        }

        return "order by " . \implode(", ", $output);
    }

    /**
     * Format the whole group by clause.
     *
     * @param Expression[] $groups
     *   Array of column names or aliases
     */
    protected function formatGroupBy(array $groups): string
    {
        if (!$groups) {
            return '';
        }

        $output = [];
        foreach ($groups as $group) {
            $output[] = $this->format($group);
        }

        return "group by " . \implode(", ", $output);
    }

    /**
     * Format a single join statement.
     */
    protected function formatJoinItem(Join $join): string
    {
        switch ($join->mode) {

            case Query::JOIN_NATURAL:
                $prefix = 'natural join';
                break;

            case Query::JOIN_LEFT:
            case Query::JOIN_LEFT_OUTER:
                $prefix = 'left outer join';
                break;

            case Query::JOIN_RIGHT:
            case Query::JOIN_RIGHT_OUTER:
                $prefix = 'right outer join';
                break;

            case Query::JOIN_INNER:
            default:
                $prefix = 'inner join';
                break;
        }

        $condition = $join->condition;

        if ($condition->isEmpty()) {
            return \sprintf(
                "%s %s",
                $prefix,
                $this->formatExpressionRelation($join->relation)
            );
        } else {
            return \sprintf(
                "%s %s on (%s)",
                $prefix,
                $this->formatExpressionRelation($join->relation),
                $this->formatWhere($condition)
            );
        }
    }

    /**
     * Format all join statements.
     *
     * @param Join[] $joins
     */
    protected function formatJoin(array $joins): string
    {
        if (!$joins) {
            return '';
        }

        $output = [];

        foreach ($joins as $join) {
            $output[] = $this->formatJoinItem($join);
        }

        return \implode("\n", $output);
    }

    /**
     * Format all update from statement.
     *
     * @param UpdateQuery $query
     * @param array $joins
     *   Each relation is an array that must contain:
     *     - key must be the relation alias
     *     - 0: ExpressionRelation relation name
     *     - 1: Where or null condition
     *     - 2: Query::JOIN_* constant
     */
    protected function formatUpdateFrom(UpdateQuery $query, array $joins): string
    {
        if (!$joins) {
            return '';
        }

        $output = [];

        $first = \array_shift($joins);
        \assert($first instanceof Join);

        // First join must be an inner join, there is no choice, and first join
        // condition will become a where clause in the global query instead
        if (!\in_array($first->mode, [Query::JOIN_INNER, Query::JOIN_NATURAL])) {
            throw new QueryError("first join in an update query must be inner or natural, it will serve as the first from table");
        }

        $output[] = \sprintf("from %s", $this->formatExpressionRelation($first->relation));
        if (!$first->condition->isEmpty()) {
            $query->getWhere()->expression($first->condition);
        }

        // Format remaining joins normally, most database servers can do that
        // at least PostgreSQL and SQLServer do
        if ($joins) {
            foreach ($joins as $join) {
                $output[] = $this->formatJoinItem($join);
            }
        }

        return \implode("\n", $output);
    }

    /**
     * Format all delete using statement.
     *
     * @param DeleteQuery $query
     * @param array $joins
     *   Each relation is an array that must contain:
     *     - key must be the relation alias
     *     - 0: ExpressionRelation relation name
     *     - 1: Where or null condition
     *     - 2: Query::JOIN_* constant
     */
    protected function formatDeleteUsing(DeleteQuery $query, array $joins): string
    {
        if (!$joins) {
            return '';
        }

        $output = [];

        $first = \array_shift($joins);
        \assert($first instanceof Join);

        // First join must be an inner join, there is no choice, and first join
        // condition will become a where clause in the global query instead
        if (!\in_array($first->mode, [Query::JOIN_INNER, Query::JOIN_NATURAL])) {
            throw new QueryError("first join in an delete query must be inner or natural, it will serve as the first using table");
        }

        $output[] = \sprintf("using %s", $this->formatExpressionRelation($first->relation));
        if (!$first->condition->isEmpty()) {
            $query->getWhere()->expression($first->condition);
        }

        // Format remaining joins normally, most database servers can do that
        // at least PostgreSQL and SQLServer do
        if ($joins) {
            foreach ($joins as $join) {
                $output[] = $this->formatJoinItem($join);
            }
        }

        return \implode("\n", $output);
    }

    /**
     * Format range statement.
     *
     * @param int $limit
     *   O means no limit
     * @param int $offset
     *   0 means default offset
     */
    protected function formatRange(int $limit = 0, int $offset = 0): string
    {
        if ($limit) {
            return \sprintf('limit %d offset %d', $limit, $offset);
        } else if ($offset) {
            return \sprintf('offset %d', $offset);
        } else {
            return '';
        }
    }

    /**
     * Format value list.
     *
     * @param mixed[] $arguments
     *   Arbitrary arguments
     * @param string $type = null
     *   Data type of arguments
     */
    protected function formatValueList(array $arguments): string
    {
        return \implode(
            ', ',
            \array_map(
                function ($value) {
                    if ($value instanceof Statement) {
                        return $this->format($value);
                    } else {
                        return '?';
                    }
                },
                $arguments
            )
        );
    }

    /**
     * Format placeholder for a single value.
     *
     * @param mixed $argument
     */
    protected function formatPlaceholder($argument): string
    {
        return '?';
    }

    /**
     * Format where instance.
     */
    protected function formatWhere(Where $where): string
    {
        if ($where->isEmpty()) {
            // Definitely legit (except for pgsql which awaits a bool)
            return '1';
        }

        $output = [];

        foreach ($where->getConditions() as $condition) {
            $value = $condition->value;
            $column = $condition->column;

            // Do not allow an empty where to be displayed
            if ($value instanceof Where && $value->isEmpty()) {
                continue;
            }

            $columnString = '';
            $valueString = '';

            if ($column) {
                $columnString = $this->format($column);
            }

            if ($value instanceof Query) {
                $valueString = \sprintf('(%s)', $this->format($value));
            } else if ($value instanceof Expression) {
                $valueString = $this->format($value);
            } else if ($value instanceof Statement) {
                $valueString = \sprintf('(%s)', $this->format($value));
            } else if (\is_array($value)) {
                $valueString = \sprintf("(%s)", $this->formatValueList($value));
            } else {
                $valueString = $this->formatPlaceholder($value);
            }

            if (!$column) {
                switch ($operator = $condition->operator) {

                    case Where::EXISTS:
                    case Where::NOT_EXISTS:
                        $output[] = \sprintf('%s %s', $operator, $valueString);
                        break;

                    case Where::IS_NULL:
                    case Where::NOT_IS_NULL:
                        $output[] = \sprintf('%s %s', $valueString, $operator);
                        break;

                    default:
                        $output[] = $valueString;
                        break;
                }
            } else {
                switch ($operator = $condition->operator) {

                    case Where::EXISTS:
                    case Where::NOT_EXISTS:
                        $output[] = \sprintf('%s %s', $operator, $valueString);
                        break;

                    case Where::IS_NULL:
                    case Where::NOT_IS_NULL:
                        $output[] = \sprintf('%s %s', $columnString, $operator);
                        break;

                    case Where::BETWEEN:
                    case Where::NOT_BETWEEN:
                        $output[] = \sprintf('%s %s ? and ?', $columnString, $operator);
                        break;

                    default:
                        $output[] = \sprintf('%s %s %s', $columnString, $operator, $valueString);
                        break;
                }
            }
        }

        return \implode("\n" . $where->getOperator() . ' ', $output);
    }

    /**
     * When no values are set in an insert query, what should we write?
     */
    protected function formatInsertNoValuesStatement(): string
    {
        return "DEFAULT VALUES";
    }

    /**
     * Format array of with statements.
     *
     * @param array $withs
     *   Each join is an array that must contain:
     *     - key does not matter
     *     - 0: string temporary table alias
     *     - 1: SelectQuery
     *     - 2: bool is recursive or not
     */
    protected function formatWith(array $withs): string
    {
        if (!$withs) {
            return '';
        }

        $output = [];

        foreach ($withs as /* $alias => */ $with) {
            if (false) {

            }

            $output[] = \sprintf(
                "%s as (%s)",
                $this->escaper->escapeIdentifier($with[0]),
                $this->formatQuerySelect($with[1])
            );
        }

        return \sprintf('with %s', \implode(', ', $output));
    }

    /**
     * Format a constant table expression.
     */
    protected function formatExpressionConstantTable(ExpressionConstantTable $constantTable): string
    {
        $valueCount = $constantTable->getValueCount();
        $columnCount = $constantTable->getColumnCount();

        if (!$valueCount) {
            return "values ()";
        }

        $output = [];

        // @todo support raw expressions within
        for ($i = 0; $i < $valueCount; ++$i) {
            $output[] = \sprintf(
                "(%s)",
                \implode(', ', \array_fill(0, $columnCount, '?'))
            );
        }

        return "values " . \implode(", ", $output);
    }

    /**
     * Format a column name list.
     */
    protected function formatColumnNameList(array $columnNames): string
    {
        return \implode(
            ', ',
            \array_map(
                function ($column) {
                    return $this->escaper->escapeIdentifier($column);
                },
                $columnNames
            )
        );
    }

    /**
     * Format given merge query.
     */
    protected function formatQueryMerge(MergeQuery $query): string
    {
        $output = [];

        if (!$relation = $query->getRelation()) {
            throw new QueryError("Merge query must have a relation.");
        }

        $columns = $query->getAllColumns();
        $escapedInsertRelation = $this->escaper->escapeIdentifier($relation->getName());
        $escapedUsingAlias = $this->escaper->escapeIdentifier($query->getUsingRelationAlias());

        $output[] = $this->formatWith($query->getAllWith());

        // From SQL:2003 standard, MERGE queries don't have table alias.
        $output[] = "merge into " . $escapedInsertRelation;

        // USING
        $using = $query->getQuery();
        if ($using instanceof ExpressionConstantTable) {
            $output[] = \sprintf("using %s as %s", $this->format($using), $escapedUsingAlias);
        } else {
            $output[] = \sprintf("using (%s) as %s", $this->format($using), $escapedUsingAlias);
        }

        // Build USING columns map.
        $usingColumnMap = [];
        foreach ($columns as $column) {
            $usingColumnMap[$column] = $escapedUsingAlias . "." . $this->escaper->escapeIdentifier($column);
        }

        // WHEN MATCHED THEN
        switch ($mode = $query->getConflictBehaviour()) {

            case Query::CONFLICT_IGNORE:
                // Do nothing.
                break;

            case Query::CONFLICT_UPDATE:
                // Exclude primary key from the UPDATE statement.
                $key = $query->getKey();
                $setColumnMap = [];
                foreach ($usingColumnMap as $column => $usingColumnExpression) {
                    if (!\in_array($column, $key)) {
                        $setColumnMap[$column] = ExpressionRaw::create($usingColumnExpression);
                    }
                }
                $output[] = "when matched then update set";
                $output[] = $this->formatUpdateSet($setColumnMap);
                break;

            default:
                throw new QueryError(\sprintf("Unsupport merge conflict mode: %s", (string) $mode));
        }

        // WHEN NOT MATCHED THEN
        $output[] = \sprintf(
            "when not matched then insert into %s (%s)",
            $escapedInsertRelation,
            $this->formatColumnNameList($columns)
        );
        $output[] = \sprintf("values (%s)", \implode(', ', $usingColumnMap));

        // RETURNING
        $return = $query->getAllReturn();
        if ($return) {
            $output[] = \sprintf("returning %s", $this->formatReturning($return));
        }

        return \implode("\n", $output);
    }

    /**
     * Format given insert query
     */
    protected function formatQueryInsert(InsertQuery $query): string
    {
        $output = [];

        $columns = $query->getAllColumns();

        if (!$relation = $query->getRelation()) {
            throw new QueryError("Insert query must target a table.");
        }

        $output[] = $this->formatWith($query->getAllWith());
        $output[] = \sprintf(
            "insert into %s",
            // From SQL 92 standard, INSERT queries don't have table alias
            $this->escaper->escapeIdentifier($relation->getName())
        );

        // Columns.
        if ($columns) {
            $output[] = \sprintf("(%s)", $this->formatColumnNameList($columns));
        }

        $using = $query->getQuery();
        if ($using instanceof ExpressionConstantTable) {
            if (\count($columns)) {
                $output[] = $this->format($using);
            } else {
                // Assume there is no specific values, for PostgreSQL, we need to set
                // "DEFAULT VALUES" explicitely, for MySQL "() VALUES ()" will do the
                // trick
                $output[] = $this->formatInsertNoValuesStatement();
            }
        } else {
            $output[] = $this->format($using);
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = \sprintf("returning %s", $this->formatReturning($return));
        }

        return \implode("\n", $output);
    }

    /**
     * Format given delete query.
     */
    protected function formatQueryDelete(DeleteQuery $query): string
    {
        $output = [];

        if (!$relation = $query->getRelation()) {
            throw new QueryError("Delete query must target a table");
        }

        $output[] = $this->formatWith($query->getAllWith());
        // This is not SQL-92 compatible, we are using USING..JOIN clause to
        // do joins in the DELETE query, which is not accepted by the standard.
        $output[] = \sprintf(
            "delete from %s",
            $this->formatExpressionRelation($relation)
        );

        $joins = $query->getAllJoin();
        if ($joins) {
            $output[] = $this->formatDeleteUsing($query, $joins);
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = \sprintf('where %s', $this->formatWhere($where));
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = \sprintf("returning %s", $this->formatReturning($return));
        }

        return \implode("\n", \array_filter($output));
    }

    /**
     * Format given update query.
     */
    protected function formatQueryUpdate(UpdateQuery $query): string
    {
        $output = [];

        $columns = $query->getUpdatedColumns();
        if (empty($columns)) {
            throw new QueryError("Cannot run an update query without any columns to update.");
        }

        if (!$relation = $query->getRelation()) {
            throw new QueryError("update query must have a relation");
        }

        $output[] = $this->formatWith($query->getAllWith());
        // From the SQL 92 standard (which PostgreSQL does support here) the
        // FROM and JOIN must be written AFTER the SET clause. MySQL does not.
        $output[] = \sprintf(
            "update %s\nset\n%s",
            $this->formatExpressionRelation($relation),
            $this->formatUpdateSet($columns)
        );

        $joins = $query->getAllJoin();
        if ($joins) {
            $output[] = $this->formatUpdateFrom($query, $joins);
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = \sprintf('where %s', $this->formatWhere($where));
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = \sprintf("returning %s", $this->formatReturning($return));
        }

        return \implode("\n", \array_filter($output));
    }

    /**
     * Format given select query.
     */
    protected function formatQuerySelect(SelectQuery $query): string
    {
        $output = [];
        $output[] = $this->formatWith($query->getAllWith());

        if ($relation = $query->getRelation()) {
            $output[] = \sprintf(
                "select %s\nfrom %s\n%s",
                $this->formatSelect($query->getAllColumns()),
                $this->formatExpressionRelation($relation),
                $this->formatJoin($query->getAllJoin())
            );
        } else {
            $output[] = \sprintf(
                "select %s\n%s",
                $this->formatSelect($query->getAllColumns()),
                $this->formatJoin($query->getAllJoin())
            );
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = \sprintf('where %s', $this->formatWhere($where));
        }

        $output[] = $this->formatGroupBy($query->getAllGroupBy());
        $output[] = $this->formatOrderBy($query->getAllOrderBy());
        $output[] = $this->formatRange(...$query->getRange());

        $having = $query->getHaving();
        if (!$having->isEmpty()) {
            $output[] = \sprintf('having %s', $this->formatWhere($having));
        }

        if ($query->isForUpdate()) {
            $output[] = "for update";
        }

        return \implode("\n", \array_filter($output));
    }

    /**
     * Format value expression.
     */
    protected function formatExpressionRaw(ExpressionRaw $expression): string
    {
        return $expression->getString();
    }

    /**
     * Format value expression.
     */
    protected function formatExpressionColumn(ExpressionColumn $column): string
    {
        // Allow selection such as "table".*
        if ('*' !== ($target = $column->getName())) {
            $target = $this->escaper->escapeIdentifier($target);
        }

        if ($relation = $column->getRelationAlias()) {
            return \sprintf(
                "%s.%s",
                $this->escaper->escapeIdentifier($relation),
                $target
            );
        }

        return $target;
    }

    /**
     * Format relation expression.
     */
    protected function formatExpressionRelation(ExpressionRelation $relation): string
    {
        $table  = $relation->getName();
        $schema = $relation->getSchema();
        $alias  = $relation->getAlias();

        if ($alias === $table) {
            $alias = null;
        }

        if ($schema && $alias) {
            return \sprintf(
                "%s.%s as %s",
                $this->escaper->escapeIdentifier($schema),
                $this->escaper->escapeIdentifier($table),
                $this->escaper->escapeIdentifier($alias)
            );
        } else if ($schema) {
            return \sprintf(
                "%s.%s",
                $this->escaper->escapeIdentifier($schema),
                $this->escaper->escapeIdentifier($table)
            );
        } else if ($alias) {
            return \sprintf(
                "%s as %s",
                $this->escaper->escapeIdentifier($table),
                $this->escaper->escapeIdentifier($alias)
            );
        } else {
            return \sprintf(
                "%s",
                $this->escaper->escapeIdentifier($table)
            );
        }
    }

    /**
     * Format value expression.
     */
    protected function formatExpressionValue(ExpressionValue $value): string
    {
        if ($type = $value->getType()) {
            return \sprintf(
                '%s::%s',
                $this->formatPlaceholder($value->getValue()),
                $type
            );
        }
        return $this->formatPlaceholder($value->getValue());
    }

    /**
     * Format like expression.
     */
    protected function formatExpressionLike(ExpressionLike $value): string
    {
        if ($value->hasValue()) {
            $pattern = $value->getPattern(
                $this->escaper->escapeLike(
                    $value->getUnsaveValue()
                )
            );
        } else {
            $pattern = $value->getPattern();
        }

        return \sprintf('%s %s %s',
            $this->format($value->getColumn()),
            $value->getOperator(),
            $this->escaper->escapeLiteral($pattern)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function format(Statement $query): string
    {
        if ($query instanceof ExpressionColumn) {
            return $this->formatExpressionColumn($query);
        } else if ($query instanceof ExpressionRaw) {
            return $this->formatExpressionRaw($query);
        } else if ($query instanceof ExpressionRelation) {
            return $this->formatExpressionRelation($query);
        } else if ($query instanceof ExpressionValue) {
            return $this->formatExpressionValue($query);
        } else if ($query instanceof ExpressionLike) {
            return $this->formatExpressionLike($query);
        } else if ($query instanceof ExpressionConstantTable) {
            return $this->formatExpressionConstantTable($query);
        } else if ($query instanceof Where) {
            return $this->formatWhere($query);
        } else if ($query instanceof DeleteQuery) {
            return $this->formatQueryDelete($query);
        } else if ($query instanceof SelectQuery) {
            return $this->formatQuerySelect($query);
        } else if ($query instanceof MergeQuery) {
            return $this->formatQueryMerge($query);
        } else if ($query instanceof InsertQuery) {
            return $this->formatQueryInsert($query);
        } else if ($query instanceof UpdateQuery) {
            return $this->formatQueryUpdate($query);
        }

        throw new \InvalidArgumentException();
    }
}
