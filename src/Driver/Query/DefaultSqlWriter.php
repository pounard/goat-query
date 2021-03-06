<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

use Goat\Driver\Platform\Escaper\Escaper;
use Goat\Query\DeleteQuery;
use Goat\Query\Expression;
use Goat\Query\InsertQuery;
use Goat\Query\MergeQuery;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use Goat\Query\Statement;
use Goat\Query\UpdateQuery;
use Goat\Query\Where;
use Goat\Query\Expression\AliasedExpression;
use Goat\Query\Expression\BetweenExpression;
use Goat\Query\Expression\CastExpression;
use Goat\Query\Expression\ColumnExpression;
use Goat\Query\Expression\ComparisonExpression;
use Goat\Query\Expression\ConstantRowExpression;
use Goat\Query\Expression\ConstantTableExpression;
use Goat\Query\Expression\LikeExpression;
use Goat\Query\Expression\RawExpression;
use Goat\Query\Expression\TableExpression;
use Goat\Query\Expression\ValueExpression;
use Goat\Query\Partial\Column;
use Goat\Query\Partial\Join;
use Goat\Query\Partial\With;
use Goat\Query\RawQuery;

/**
 * Standard SQL query formatter: this implementation conforms as much as it
 * can to SQL-92 standard, and higher revisions for some functions.
 *
 * Please note that the main target is PostgreSQL, and PostgreSQL has an
 * excellent SQL-92|1999|2003|2006|2008|2011 standard support, almost
 * everything in here except MERGE queries is supported by PostgreSQL.
 *
 * We could have override the CastExpression formatting for PostgreSQL using
 * its ::TYPE shorthand, but since that is is only a legacy syntax shorthand
 * and that the standard CAST(value AS type) expression may require less
 * parenthesis in some cases, it's simply eaiser to keep the CAST() syntax.
 *
 * @todo Implement properly and unit test using a server connexion the cast
 *   syntax for both pgsql and mysql, especially mysql that will need different
 *   types names for casting.
 *
 * All methods starting with "format" do handle a known Expression class,
 * whereas all methods starting with "do" will handle an internal behaviour.
 */
class DefaultSqlWriter implements SqlWriter
{
    /**
     * Escape sequence matching magical regex.
     *
     * Order is important:
     *
     *   - ESCAPE will match all driver-specific string escape sequence,
     *     therefore will prevent any other matches from happening inside,
     *
     *   - "??" will always superseed "?*",
     *
     *   - "?::WORD" will superseed "?",
     *
     *   - any "::WORD" sequence, which is a valid SQL cast, will be left
     *     as-is and required no rewrite.
     *
     * After some thoughts, this needs serious optimisation.
     *
     * I believe that a real parser would be much more efficient, if it was
     * written in any language other than PHP, but right now, preg will
     * actually be a lot faster than we will ever be.
     *
     * This regex is huge, but contain no backward lookup, does not imply
     * any recursivity, it should be fast enough.
     */
    const PARAMETER_MATCH = '@
        ESCAPE
        (\?\?)|                 # Matches ??
        (\?((\:\:([\w]+))|))    # Matches ?[::WORD] (placeholders)
        @x';

    private string $matchParametersRegex;
    protected Escaper $escaper;

    public function __construct(Escaper $escaper)
    {
        $this->escaper = $escaper;
        $this->buildParameterRegex();
    }

    /**
     * {@inheritdoc}
     */
    final public function prepare($query, ?array $arguments = null, ?WriterContext $context = null): FormattedQuery
    {
        $preparedSQL = $identifier = null;
        $context = $context ?? new WriterContext();

        if (\is_string($query)) {
            $preparedSQL = $this->parseExpression(new RawExpression($query, $arguments ?? []), $context);
        } else if ($query instanceof Statement) {
            if ($query instanceof Query) {
                $identifier = $query->getIdentifier();
            }
            $preparedSQL = $this->format($query, $context);
        } else {
            throw new QueryError(\sprintf("query must be a bare string or an instance of %s", Statement::class));
        }

        return new FormattedQuery($preparedSQL, $identifier, $context->getArgumentBag());
    }

    /**
     * Uses the connection driven escape sequences to build the parameter
     * matching regex.
     */
    private function buildParameterRegex(): void
    {
        // Please see this really excellent Stack Overflow answer:
        //   https://stackoverflow.com/a/23589204
        $patterns = [];

        foreach ($this->escaper->getEscapeSequences() as $sequence) {
            $sequence = \preg_quote($sequence);
            $patterns[] = \sprintf("%s.+?%s", $sequence, $sequence);
        }

        if ($patterns) {
            $this->matchParametersRegex = \str_replace('ESCAPE', \sprintf("(%s)|", \implode("|", $patterns)), self::PARAMETER_MATCH);
        } else {
            $this->matchParametersRegex = \str_replace('ESCAPE', self::PARAMETER_MATCH);
        }
    }

    /**
     * Converts all typed placeholders in the query and replace them with the
     * correct placeholder syntax. It matches ? or ?::TYPE syntaxes, outside of
     * SQL dialect escape sequences, everything else will be left as-is.
     *
     * Real conversion is done later in the runner implementation, when it
     * reconciles the user given arguments with the type information found while
     * formating or parsing the SQL.
     *
     * @param string $formattedSQL
     *   Raw SQL from user input, or formatted SQL from the formatter
     *
     * @return array
     *   First value is the query string, second is the reworked array
     *   of parameters, if conversions were needed
     */
    protected function parseExpression(/* RawExpression | RawQuery */ $expression, WriterContext $context): string
    {
        \assert($expression instanceof RawQuery || $expression instanceof RawExpression);
        $asString = $expression->getString();
        $values = $expression->getArguments();

        // @todo \str_contains() polifill for lower php versions?
        if (!$values && false === \str_contains($asString, '?')) {
            // Performance shortcut for expressions containing no arguments.
            return $asString;
        }

        // See https://stackoverflow.com/a/3735908 for the  starting
        // sequence explaination, the rest should be comprehensible.
        $localIndex = -1;
        return \preg_replace_callback(
            $this->matchParametersRegex,
            function ($matches) use (&$localIndex, $values, $context) {
                $match  = $matches[0];

                if ('??' === $match) {
                    return $this->escaper->unescapePlaceholderChar();
                }
                if ('?' !== $match[0]) {
                    return $match;
                }

                $localIndex++;
                $value = $values[$localIndex] ?? null;

                if ($value instanceof Statement) {
                    // @todo prepare() Not in interface
                    return $this->format($value, $context);
                } else {
                    $index = $context->append($value, empty($matches[6]) ? null : $matches[6]);

                    return $this->escaper->writePlaceholder($index);
                }
            },
            $asString
        );
    }

    /**
     * Format a single set clause (update queries).
     *
     * @param string $columnName
     * @param string|Expression $expression
     */
    protected function doFormatUpdateSetItem(WriterContext $context, string $columnName, $expression): string
    {
        $columnString = $this->escaper->escapeIdentifier($columnName);

        if ($expression instanceof Expression) {
            return $columnString . ' = ' . $this->format($expression, $context);
        }
        if ($expression instanceof Statement) {
            return $columnString . ' = (' . $this->format($expression, $context) . ')';
        }
        return $columnString . ' = ' . $this->escaper->escapeLiteral($expression);
    }

    /**
     * Format all set clauses (update queries).
     *
     * @param string[]|Expression[] $columns
     *   Keys are column names, values are strings or Expression instances
     */
    protected function doFormatUpdateSet(WriterContext $context, array $columns): string
    {
        $output = [];

        foreach ($columns as $column => $statement) {
            $output[] = $this->doFormatUpdateSetItem($context, $column, $statement);
        }

        return \implode(",\n", $output);
    }

    /**
     * Format projection for a single select column or statement.
     */
    protected function doFormatSelectItem(WriterContext $context, Column $column): string
    {
        $output = $this->format($column->expression, $context);

        // Add parenthesis when necessary.
        if ($column->expression instanceof ConstantRowExpression) {
            $output = 'row' . $output;
        } else if ($column->expression instanceof ConstantTableExpression) {
            $output = '(' . $output . ')';
        } else if ($column->expression instanceof Query) {
            $output = '(' . $output . ')';
        }

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
     * Format SELECT columns.
     *
     * @param Column[] $columns
     */
    protected function doFormatSelect(WriterContext $context, array $columns): string
    {
        if (!$columns) {
            return '*';
        }

        $output = [];

        foreach ($columns as $column) {
            $output[] = $this->doFormatSelectItem($context, $column);
        }

        return \implode(",\n", $output);
    }

    /**
     * Format the whole projection.
     *
     * @param array $return
     *   Each column is an array that must contain:
     *     - 0: string or Statement: column name or SQL statement
     *     - 1: column alias, can be empty or null for no aliasing
     */
    protected function doFormatReturning(WriterContext $context, array $return): string
    {
        return $this->doFormatSelect($context, $return);
    }

    /**
     * Format a single order by.
     *
     * @param string|Expression $column
     * @param int $order
     *   Query::ORDER_* constant.
     * @param int $null
     *   Query::NULL_* constant.
     */
    protected function doFormatOrderByItem(WriterContext $context, $column, int $order, int $null): string
    {
        $column = $this->format($column, $context);

        if (Query::ORDER_ASC === $order) {
            $orderStr = 'asc';
        } else {
            $orderStr = 'desc';
        }

        switch ($null) {

            case Query::NULL_FIRST:
                return $column . ' ' . $orderStr . ' nulls first';
                break;

            case Query::NULL_LAST:
                return $column . ' ' . $orderStr . ' nulls last';

            case Query::NULL_IGNORE:
            default:
                return $column . ' ' . $orderStr;
        }
    }

    /**
     * Format the whole order by clause.
     *
     * @todo Convert $orders items to an Order class.
     *
     * @param array $orders
     *   Each order is an array that must contain:
     *     - 0: Expression
     *     - 1: Query::ORDER_* constant
     *     - 2: Query::NULL_* constant
     */
    protected function doFormatOrderBy(WriterContext $context, array $orders): string
    {
        if (!$orders) {
            return '';
        }

        $output = [];

        foreach ($orders as $data) {
            list($column, $order, $null) = $data;
            $output[] = $this->doFormatOrderByItem($context, $column, $order, $null);
        }

        return "order by " . \implode(", ", $output);
    }

    /**
     * Format the whole group by clause.
     *
     * @param Expression[] $groups
     *   Array of column names or aliases.
     */
    protected function doFormatGroupBy(WriterContext $context, array $groups): string
    {
        if (!$groups) {
            return '';
        }

        $output = [];
        foreach ($groups as $group) {
            $output[] = $this->format($group, $context);
        }

        return "group by " . \implode(", ", $output);
    }

    /**
     * Format a single join statement.
     */
    protected function doFormatJoinItem(WriterContext $context, Join $join): string
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

        // @todo parenthesis when necessary
        if ($condition->isEmpty()) {
            return $prefix . ' ' . $this->format($join->table, $context);
        } else {
            return $prefix . ' ' . $this->format($join->table, $context) . ' on (' . $this->formatWhere($context, $condition) . ')';
        }
    }

    /**
     * Format all join statements.
     *
     * @param Join[] $join
     */
    protected function doFormatJoin(WriterContext $context, array $join, bool $transformFirstJoinAsFrom = false, ?string $fromPrefix = null, $query = null): string
    {
        if (!$join) {
            return '';
        }

        $output = [];

        if ($transformFirstJoinAsFrom) {
            $first = \array_shift($join);
            \assert($first instanceof Join);

            // First join must be an inner join, there is no choice, and first join
            // condition will become a where clause in the global query instead
            if (!\in_array($first->mode, [Query::JOIN_INNER, Query::JOIN_NATURAL])) {
                throw new QueryError("First join in an update query must be inner or natural, it will serve as the first FROM or USING table.");
            }

            if ($fromPrefix) {
                $output[] = $fromPrefix . ' ' . $this->format($first->table, $context);
            } else {
                $output[] = $this->format($first->table, $context);
            }

            if (!$first->condition->isEmpty()) {
                if (!$query) {
                    throw new QueryError("Something very bad happened.");
                }
                $query->getWhere()->expression($first->condition);
            }
        }

        foreach ($join as $item) {
            $output[] = $this->doFormatJoinItem($context, $item);
        }

        return \implode("\n", $output);
    }

    /**
     * Format all update from statement.
     *
     * @param Expression[] $from
     */
    protected function doFormatFrom(WriterContext $context, array $from, ?string $prefix): string
    {
        if (!$from) {
            return '';
        }

        $output = [];

        foreach ($from as $item) {
            \assert($item instanceof Expression);

            // @todo parenthesis when necessary
            $output[] = $this->format($item, $context);

            if ($item instanceof ConstantTableExpression) {
                if ($columnAliases = $item->expression->getColumns()) {
                    $output[] = ' (' . $this->doFormatColumnNameList($context, $columnAliases) . ')';
                }
            }
        }

        if ($prefix) {
            return $prefix . ' ' . \implode(', ', $output);
        }

        return \implode(", ", $output);
    }

    /**
     * When no values are set in an insert query, what should we write?
     */
    protected function doFormatInsertNoValuesStatement(WriterContext $context): string
    {
        return "DEFAULT VALUES";
    }

    /**
     * Format array of with statements.
     *
     * @param With[] $with
     */
    protected function doFormatWith(WriterContext $context, array $with): string
    {
        if (!$with) {
            return '';
        }

        $output = [];

        foreach ($with as $item) {
            \assert($item instanceof With);

            // @todo I don't think I can do better than that, but I'm really sorry.
            if ($item->table instanceof ConstantTableExpression && ($columnAliases = $item->table->getColumns())) {
                $output[] = $this->escaper->escapeIdentifier($item->alias) . ' (' . $this->doFormatColumnNameList($context, $columnAliases) . ') as (' . $this->format($item->table, $context) . ')';
            } else {
                $output[] = $this->escaper->escapeIdentifier($item->alias) . ' as (' . $this->format($item->table, $context) . ')';
            }
        }

        return 'with ' . \implode(', ', $output);
    }

    /**
     * Format range statement.
     *
     * @param int $limit
     *   O means no limit
     * @param int $offset
     *   0 means default offset
     */
    protected function doFormatRange(WriterContext $context, int $limit = 0, int $offset = 0): string
    {
        if ($limit) {
            return 'limit ' . $limit . ' offset ' . $offset;
        }
        if ($offset) {
            return 'offset ' . $offset;
        }
        return '';
    }

    /**
     * Format a column name list.
     */
    protected function doFormatColumnNameList(WriterContext $context, array $columnNames): string
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
     * Format generic comparison expression.
     */
    protected function formatComparisonExpression(ComparisonExpression $expression, WriterContext $context): string
    {
        $output = '';

        $left = $expression->getLeft();
        $right = $expression->getRight();
        $operator = $expression->getOperator();

        if ($left) {
            if ($left instanceof Query || $left instanceof ConstantTableExpression) {
                $output .= '(' . $this->format($left, $context) . ')';
            } else {
                $output .= $this->format($left, $context);
            }
        }

        if ($operator) {
            $output .= ' ' . $operator;
        }

        if ($right) {
            if ($right instanceof Query || $right instanceof ConstantTableExpression) {
                $output .= ' (' . $this->format($right, $context) . ')';
            } else {
                $output .= ' ' . $this->format($right, $context);
            }
        }

        return $output;
    }

    /**
     * Format BETWEEN expression.
     */
    protected function formatBetweenExpression(BetweenExpression $expression, WriterContext $context): string
    {
        $column = $expression->getColumn();
        $from = $expression->getFrom();
        $to = $expression->getTo();
        $operator = $expression->getOperator();

        return $this->format($column, $context) . ' ' . $operator . ' ' . $this->format($from, $context) . ' and ' . $this->format($to, $context);
    }

    /**
     * Format where instance.
     */
    protected function formatWhere(WriterContext $context, Where $where): string
    {
        if ($where->isEmpty()) {
            // Definitely legit, except for PostgreSQL which seems to require
            // a boolean value for those expressions. In theory, booleans are
            // part of the SQL standard, but a lot of RDBMS don't support them,
            // so we keep the "1" here.
            return '1';
        }

        $output = '';

        $operator = $where->getOperator();
        $first = true;

        foreach ($where->getConditions() as $expression) {
            // Do not allow an empty where to be displayed.
            if ($expression instanceof Where && $expression->isEmpty()) {
                continue;
            }

            if ($first) {
                $first = false;
            } else {
                $output .= "\n" . $operator . ' ';
            }

            if ($expression instanceof Where) {
                $output .= '(' . $this->format($expression, $context) . ')';
            } else {
                $output .= $this->format($expression, $context);
            }
        }

        return $output;
    }

    /**
     * Format a constant table expression.
     */
    protected function formatConstantTableExpression(ConstantTableExpression $constantTable, WriterContext $context): string
    {
        if (!$constantTable->getRowCount()) {
            return "values ()";
        }

        return "values " . \implode(
            ", ",
            \array_map(
                fn ($row) => $this->formatConstantRowExpression($row, $context),
                $constantTable->getRows(),
            )
        );
    }

    /**
     * Format an arbitrary row of values.
     */
    protected function formatConstantRowExpression(ConstantRowExpression $row, WriterContext $context): string
    {
        return '(' . \implode(
            ", ",
            \array_map(
                fn ($value) => ($value instanceof Query ? '(' . $this->format($value, $context) . ')' : $this->format($value, $context)),
                $row->getValues())
        ) . ')';
    }

    /**
     * Format given merge query.
     */
    protected function formatQueryMerge(MergeQuery $query, WriterContext $context): string
    {
        $output = [];

        $table = $query->getTable();
        $columns = $query->getAllColumns();
        $escapedInsertTable = $this->escaper->escapeIdentifier($table->getName());
        $escapedUsingAlias = $this->escaper->escapeIdentifier($query->getUsingTableAlias());

        $output[] = $this->doFormatWith($context, $query->getAllWith());

        // From SQL:2003 standard, MERGE queries don't have table alias.
        $output[] = "merge into " . $escapedInsertTable;

        // USING
        $using = $query->getQuery();
        if ($using instanceof ConstantTableExpression) {
            $output[] = 'using ' . $this->format($using, $context) . ' as ' . $escapedUsingAlias;
            if ($columnAliases = $using->getColumns()) {
                $output[] = ' (' . $this->doFormatColumnNameList($context, $columnAliases) . ')';
            }
        } else {
            $output[] = 'using (' . $this->format($using, $context) . ') as ' . $escapedUsingAlias;
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
                        $setColumnMap[$column] = new RawExpression($usingColumnExpression);
                    }
                }
                $output[] = "when matched then update set";
                $output[] = $this->doFormatUpdateSet($context, $setColumnMap);
                break;

            default:
                throw new QueryError(\sprintf("Unsupport merge conflict mode: %s", (string) $mode));
        }

        // WHEN NOT MATCHED THEN
        $output[] = 'when not matched then insert into ' . $escapedInsertTable;
        $output[] = '(' . $this->doFormatColumnNameList($context, $columns) . ')';
        $output[] = 'values (' . \implode(', ', $usingColumnMap) . ')';

        // RETURNING
        $return = $query->getAllReturn();
        if ($return) {
            $output[] = 'returning ' . $this->doFormatReturning($context, $return);
        }

        return \implode("\n", $output);
    }

    /**
     * Format given insert query.
     */
    protected function formatQueryInsert(InsertQuery $query, WriterContext $context): string
    {
        $output = [];

        $columns = $query->getAllColumns();

        if (!$table = $query->getTable()) {
            throw new QueryError("Insert query must target a table.");
        }

        $output[] = $this->doFormatWith($context, $query->getAllWith());
        // From SQL 92 standard, INSERT queries don't have table alias
        $output[] = 'insert into ' . $this->escaper->escapeIdentifier($table->getName());

        // Columns.
        if ($columns) {
            $output[] = '(' . $this->doFormatColumnNameList($context, $columns) . ')';
        }

        $using = $query->getQuery();
        if ($using instanceof ConstantTableExpression) {
            if (\count($columns)) {
                $output[] = $this->format($using, $context);
            } else {
                // Assume there is no specific values, for PostgreSQL, we need to set
                // "DEFAULT VALUES" explicitely, for MySQL "() VALUES ()" will do the
                // trick
                $output[] = $this->doFormatInsertNoValuesStatement($context);
            }
        } else {
            $output[] = $this->format($using, $context);
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = 'returning ' . $this->doFormatReturning($context, $return);
        }

        return \implode("\n", $output);
    }

    /**
     * Format given delete query.
     */
    protected function formatQueryDelete(DeleteQuery $query, WriterContext $context): string
    {
        $output = [];

        if (!$table = $query->getTable()) {
            throw new QueryError("Delete query must target a table.");
        }

        $output[] = $this->doFormatWith($context, $query->getAllWith());
        // This is not SQL-92 compatible, we are using USING..JOIN clause to
        // do joins in the DELETE query, which is not accepted by the standard.
        $output[] = 'delete from ' . $this->formatTableExpression($table, $context);

        $transformFirstJoinAsFrom = true;

        $from = $query->getAllFrom();
        if ($from) {
            $transformFirstJoinAsFrom = false;
            $output[] = ', ';
            $output[] = $this->doFormatFrom($context, $from, 'using');
        }

        $join = $query->getAllJoin();
        if ($join) {
            $output[] = $this->doFormatJoin($context, $join, $transformFirstJoinAsFrom, 'using', $query);
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = 'where ' . $this->formatWhere($context, $where);
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = 'returning ' . $this->doFormatReturning($context, $return);
        }

        return \implode("\n", \array_filter($output));
    }

    /**
     * Format given update query.
     */
    protected function formatQueryUpdate(UpdateQuery $query, WriterContext $context): string
    {
        $output = [];

        $columns = $query->getUpdatedColumns();
        if (empty($columns)) {
            throw new QueryError("Cannot run an update query without any columns to update.");
        }

        if (!$table = $query->getTable()) {
            throw new QueryError("Update query must have a table.");
        }

        //
        // Specific use case for DELETE, there might be JOIN, this valid for
        // all of PostgreSQL, MySQL and MSSQL.
        //
        // We have three variants to implement:
        //
        //  - PgSQL: UPDATE FROM a SET x = y FROM b, c JOIN d WHERE (SQL-92),
        //
        //  - MySQL: UPDATE FROM a, b, c, JOIN d SET x = y WHERE
        //
        //  - MSSQL: UPDATE SET x = y FROM a, b, c JOIN d WHERE
        //
        // Current implementation is SQL-92 standard (and PostgreSQL which
        // strictly respect the standard for most of its SQL syntax).
        //
        // Also note that MSSQL will allow UPDATE on a CTE query for example,
        // MySQL will allow UPDATE everywhere, in all cases that's serious
        // violations of the SQL standard and probably quite a dangerous thing
        // to use, so it's not officialy supported, even thought using some
        // expression magic you can write those queries.
        //

        $output[] = $this->doFormatWith($context, $query->getAllWith());
        $output[] = 'update ' . $this->formatTableExpression($table, $context);
        $output[] = 'set ' . $this->doFormatUpdateSet($context, $columns);

        $transformFirstJoinAsFrom = true;

        $from = $query->getAllFrom();
        if ($from) {
            $transformFirstJoinAsFrom = false;
            $output[] = $this->doFormatFrom($context, $from, 'from');
        }

        $join = $query->getAllJoin();
        if ($join) {
            $output[] = $this->doFormatJoin($context, $join, $transformFirstJoinAsFrom, 'from', $query);
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = 'where ' . $this->formatWhere($context, $where);
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = "returning " . $this->doFormatReturning($context, $return);
        }

        return \implode("\n", \array_filter($output));
    }

    /**
     * Format given select query.
     */
    protected function formatQuerySelect(SelectQuery $query, WriterContext $context): string
    {
        $output = [];
        $output[] = $this->doFormatWith($context, $query->getAllWith());
        $output[] = "select";
        $output[] = $this->doFormatSelect($context, $query->getAllColumns());

        $from = $query->getAllFrom();
        if ($from) {
            $output[] = $this->doFormatFrom($context, $from, 'from');
        }

        $join = $query->getAllJoin();
        if ($join) {
            $output[] = $this->doFormatJoin($context, $join);
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = 'where ' . $this->formatWhere($context, $where);
        }

        $output[] = $this->doFormatGroupBy($context, $query->getAllGroupBy());

        $having = $query->getHaving();
        if (!$having->isEmpty()) {
            $output[] = 'having ' . $this->formatWhere($context, $having);
        }

        $output[] = $this->doFormatOrderBy($context, $query->getAllOrderBy());
        $output[] = $this->doFormatRange($context, ...$query->getRange());

        foreach ($query->getUnion() as $expression) {
            $output[] = "union " . $this->format($expression, $context);
        }

        if ($query->isForUpdate()) {
            $output[] = "for update";
        }

        return \implode("\n", \array_filter($output));
    }

    /**
     * Format raw expression.
     */
    protected function formatRawExpression(RawExpression $expression, WriterContext $context): string
    {
        return $this->parseExpression($expression, $context);
    }

    /**
     * Format raw query.
     */
    protected function formatRawQuery(RawQuery $query, WriterContext $context)
    {
        return $this->parseExpression($query, $context);
    }

    /**
     * Format value expression.
     */
    protected function formatColumnExpression(ColumnExpression $column, WriterContext $context): string
    {
        // Allow selection such as "table".*
        if ('*' !== ($target = $column->getName())) {
            $target = $this->escaper->escapeIdentifier($target);
        }

        if ($table = $column->getTableAlias()) {
            return $this->escaper->escapeIdentifier($table) . '.' . $target;
        }

        return $target;
    }

    /**
     * Format table expression.
     */
    protected function formatTableExpression(TableExpression $table, WriterContext $context): string
    {
        $name = $table->getName();
        $schema = $table->getSchema();
        $alias = $table->getAlias();

        if ($alias === $name) {
            $alias = null;
        }

        if ($schema && $alias) {
            return $this->escaper->escapeIdentifier($schema) . '.' . $this->escaper->escapeIdentifier($name) . ' as ' . $this->escaper->escapeIdentifier($alias);
        }
        if ($schema) {
            return $this->escaper->escapeIdentifier($schema) . '.' . $this->escaper->escapeIdentifier($name);
        }
        if ($alias) {
            return $this->escaper->escapeIdentifier($name) . ' as ' . $this->escaper->escapeIdentifier($alias);
        }

        return $this->escaper->escapeIdentifier($name);
    }

    /**
     * Format value expression.
     */
    protected function formatValueExpression(ValueExpression $value, WriterContext $context): string
    {
        $index = $context->append($value->getValue(), $value->getType());

        // @todo For now this is hardcoded, but later this will be more generic
        //   fact is that for deambiguation, PostgreSQL needs arrays to be cast
        //   explicitly, otherwise it'll interpret it as a string; This might
        //   the case for some other types as well.

        return $this->escaper->writePlaceholder($index);
    }

    /**
     * Format cast expression.
     */
    protected function formatCastExpression(CastExpression $value, WriterContext $context): string
    {
        $expression = $value->getValue();
        $expressionString = $this->format($expression, $context);

        // Add parenthesis when necessary.
        if ($expression instanceof ConstantRowExpression) {
            $expressionString = 'row' . $expressionString;
        }

        return 'CAST(' . $expressionString . ' AS ' . $value->getCastToType() . ')';
    }

    /**
     * Format like expression.
     */
    protected function formatLikeExpression(LikeExpression $value, WriterContext $context): string
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

        return $this->format($value->getColumn(), $context) . ' ' . $value->getOperator() . ' ' . $this->escaper->escapeLiteral($pattern);
    }

    /**
     * Format an expression with an alias.
     */
    protected function formatAliasedExpression(AliasedExpression $expression, WriterContext $context): string
    {
        $decorated = $expression->getExpression();
        if ($alias = $expression->getAlias()) {
            $output = '(' . $this->format($decorated, $context) . ') as ' . $this->escaper->escapeIdentifier($alias);

            if ($decorated instanceof ConstantTableExpression && ($columnAliases = $decorated->getColumns())) {
                return $output . ' (' . $this->doFormatColumnNameList($context, $columnAliases) . ')';
            }
            return $output;
        }
        return '(' . $this->format($decorated, $context) . ')';
    }

    /**
     * {@inheritdoc}
     */
    protected function format(Statement $query, WriterContext $context): string
    {
        if ($query instanceof ColumnExpression) {
            return $this->formatColumnExpression($query, $context);
        }
        if ($query instanceof RawExpression) {
            return $this->formatRawExpression($query, $context);
        }
        if ($query instanceof TableExpression) {
            return $this->formatTableExpression($query, $context);
        }
        if ($query instanceof ValueExpression) {
            return $this->formatValueExpression($query, $context);
        }
        if ($query instanceof CastExpression) {
            return $this->formatCastExpression($query, $context);
        }
        if ($query instanceof ComparisonExpression) {
            return $this->formatComparisonExpression($query, $context);
        }
        if ($query instanceof BetweenExpression) {
            return $this->formatBetweenExpression($query, $context);
        }
        if ($query instanceof LikeExpression) {
            return $this->formatLikeExpression($query, $context);
        }
        if ($query instanceof ConstantTableExpression) {
            return $this->formatConstantTableExpression($query, $context);
        }
        if ($query instanceof ConstantRowExpression) {
            return $this->formatConstantRowExpression($query, $context);
        }
        if ($query instanceof Where) {
            return $this->formatWhere($context, $query);
        }
        if ($query instanceof DeleteQuery) {
            return $this->formatQueryDelete($query, $context);
        }
        if ($query instanceof SelectQuery) {
            return $this->formatQuerySelect($query, $context);
        }
        if ($query instanceof MergeQuery) {
            return $this->formatQueryMerge($query, $context);
        }
        if ($query instanceof InsertQuery) {
            return $this->formatQueryInsert($query, $context);
        }
        if ($query instanceof UpdateQuery) {
            return $this->formatQueryUpdate($query, $context);
        }
        if ($query instanceof AliasedExpression) {
            return $this->formatAliasedExpression($query, $context);
        }
        if ($query instanceof RawQuery) {
            return $this->formatRawQuery($query, $context);
        }

        throw new QueryError(\sprintf("Unexpected expression object type: ", \get_class($query)));
    }
}
