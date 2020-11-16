<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Query;

use Goat\Driver\Query\DefaultSqlWriter;
use Goat\Query\DeleteQuery;
use Goat\Query\Expression;
use Goat\Query\ExpressionRaw;
use Goat\Query\MergeQuery;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\UpdateQuery;

/**
 * MySQL <= 5.7
 */
class MySQLWriter extends DefaultSqlWriter
{
    /**
     * {@inheritdoc}
     */
    protected function getCastType(string $type) : string
    {
        $type = parent::getCastType($type);

        // Specific type conversion for MySQL because its CAST() function
        // does not accepts the same datatypes as the one it handles.
        if ('timestamp' === $type) {
            return 'datetime';
        } else if ('int' === \mb_substr($type, 0, 3)) {
            return 'signed integer';
        } else if ('float' === \mb_substr($type, 0, 5) || 'double' === \mb_substr($type, 0, 6)) {
            return 'decimal';
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    protected function writeCast(string $placeholder, string $type): string
    {
        // This is supposedly SQL-92 standard compliant, but can be overriden
        return 'cast(' . $placeholder . ' as ' . $type . ')';
    }

    /**
     * {@inheritdoc}
     */
    protected function formatInsertNoValuesStatement() : string
    {
        return "() VALUES ()";
    }

    /**
     * Format excluded item from INSERT or MERGE values.
     */
    protected function doFormatInsertExcludedItem($expression): Expression
    {
        if (\is_string($expression)) {
            // Let pass strings with dot inside, it might already been formatted.
            if (false !== \strpos($expression, ".")) {
                return ExpressionRaw::create($expression);
            }
            return ExpressionRaw::create("values(" . $this->escaper->escapeIdentifier($expression) . ")");
        }

        return $expression;
    }

    /**
     * This is a copy-paste of formatQueryInsertValues(). In 2.x formatter will
     * be refactored to avoid such copy/paste.
     *
     * {@inheritdoc}
     */
    protected function formatQueryMerge(MergeQuery $query) : string
    {
        $output = [];

        $columns = $query->getAllColumns();
        $isIgnore = Query::CONFLICT_IGNORE === $query->getConflictBehaviour();

        if (!$table = $query->getTable()) {
            throw new QueryError("Insert query must have a table.");
        }

        $output[] = $this->formatWith($query->getAllWith());
        if ($isIgnore) {
            // From SQL 92 standard, INSERT queries don't have table alias
            $output[] = 'insert ignore into ' . $this->escaper->escapeIdentifier($table->getName());
        } else {
            // From SQL 92 standard, INSERT queries don't have table alias
            $output[] = 'insert into ' . $this->escaper->escapeIdentifier($table->getName());
        }

        if ($columns) {
            $output[] = '(' . $this->formatColumnNameList($columns) . ')';
        }

        $output[] = $this->format($query->getQuery());

        if (!$isIgnore) {
            switch ($mode = $query->getConflictBehaviour()) {

                case Query::CONFLICT_UPDATE:
                    // Exclude primary key from the UPDATE statement.
                    $key = $query->getKey();
                    $setColumnMap = [];
                    foreach ($columns as $column) {
                        if (!\in_array($column, $key)) {
                            $setColumnMap[$column] = $this->doFormatInsertExcludedItem($column);
                        }
                    }
                    $output[] = "on duplicate key update";
                    $output[] = $this->formatUpdateSet($setColumnMap);
                    break;

                default:
                    throw new QueryError(\sprintf("Unsupported merge conflict mode: %s", (string) $mode));
            }
        }

        $return = $query->getAllReturn();
        if ($return) {
            throw new QueryError("MySQL does not support RETURNING SQL clause");
        }

        return \implode("\n", $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatQueryDelete(DeleteQuery $query) : string
    {
        $output = [];

        // MySQL need to specify on which table to delete from if there is an
        // alias on the main table, so we are going to give him this always
        // so we won't have to bother about weither or not we have other tables
        // to JOIN.
        if (!$table = $query->getTable()) {
            throw new QueryError("Delete query must have a table.");
        }

        $tableAlias = $table->getAlias() ?? $table->getName();

        // MySQL does not have USING clause, and support a non-standard way of
        // writing DELETE directly using FROM .. JOIN clauses, just like you
        // would write a SELECT, so give him that. Beware that some MySQL
        // versions will DELETE FROM all tables matching rows in the FROM,
        // hence the "table_alias.*" statement here.
        $output[] = 'delete ' . $this->escaper->escapeIdentifier($tableAlias) .  '.* from ' . $this->formatTableExpression($table);

        $from = $query->getAllFrom();
        if ($from) {
            $output[] = ', ';
            $output[] = $this->formatFrom($from);
        }

        $join = $query->getAllJoin();
        if ($join) {
            $output[] = $this->formatJoin($join);
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = 'where ' . $this->formatWhere($where);
        }

        $return = $query->getAllReturn();
        if ($return) {
            throw new QueryError("MySQL does not support RETURNING SQL clause");
        }

        return \implode("\n", \array_filter($output));
    }

    /**
     * {@inheritdoc}
     */
    protected function formatQueryUpdate(UpdateQuery $query) : string
    {
        $output = [];

        $columns = $query->getUpdatedColumns();
        if (empty($columns)) {
            throw new QueryError("cannot run an update query without any columns to update");
        }

        // From the SQL 92 standard (which PostgreSQL does support here) the
        // FROM and JOIN must be written AFTER the SET clause. MySQL does not.
        $output[] = 'update ' . $this->formatTableExpression($query->getTable());

        // MySQL don't do UPDATE t1 SET [...] FROM t2 but uses the SELECT
        // syntax and just append the set after the JOIN clause.
        $from = $query->getAllFrom();
        if ($from) {
            $output[] = ', ';
            $output[] = $this->formatFrom($from);
        }

        $join = $query->getAllJoin();
        if ($join) {
            $output[] = $this->formatJoin($join);
        }

        // SET clause.
        $output[] = 'set ' . $this->formatUpdateSet($columns) . "\n";

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = 'where ' . $this->formatWhere($where);
        }

        $return = $query->getAllReturn();
        if ($return) {
            throw new QueryError("MySQL does not support RETURNING SQL clause");
        }

        return \implode("\n", $output);
    }
}
