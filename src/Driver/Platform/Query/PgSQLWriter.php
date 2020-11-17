<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Query;

use Goat\Driver\Query\DefaultSqlWriter;
use Goat\Query\MergeQuery;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\Expression\RawExpression;

/**
 * PostgreSQL >= 8.4.
 *
 * Activily tested with versions from 9.5 to 11.
 */
class PgSQLWriter extends DefaultSqlWriter
{
    /**
     * {@inheritdoc}
     */
    protected function writeCast(string $placeholder, string $type): string
    {
        // No surprises there, PostgreSQL is very straight-forward and just
        // uses the datatypes as it handles it. Very stable and robust.
        return $placeholder . '::' . $type;
    }

    /**
     * {@inheritdoc}
     */
    protected function formatInsertNoValuesStatement(): string
    {
        return "DEFAULT VALUES";
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

        if (!$table = $query->getTable()) {
            throw new QueryError("Insert query must have a table.");
        }

        $output[] = $this->formatWith($query->getAllWith());
        // From SQL 92 standard, INSERT queries don't have table alias
        $output[] = 'insert into ' . $this->escaper->escapeIdentifier($table->getName());

        // @todo skip column names if numerical
        if ($columns) {
            $output[] = '(' . $this->formatColumnNameList($columns) . ')';
        }

        $output[] = $this->format($query->getQuery());

        switch ($mode = $query->getConflictBehaviour()) {

            case Query::CONFLICT_IGNORE:
                // Do nothing.
                $output[] = "on conflict do nothing";
                break;

            case Query::CONFLICT_UPDATE:
                $key = $query->getKey();
                if (!$key) {
                    throw new QueryError(\sprintf("Key must be specified calling %s::setKey() when on conflict update is set.", \get_class($query)));
                }

                // Exclude primary key from the UPDATE statement.
                $setColumnMap = [];
                foreach ($columns as $column) {
                    if (!\in_array($column, $key)) {
                        $setColumnMap[$column] = RawExpression::create("excluded." . $this->escaper->escapeIdentifier($column));
                    }
                }
                $output[] = 'on conflict (' . $this->formatColumnNameList($key) . ')';
                $output[] = 'do update set';
                $output[] = $this->formatUpdateSet($setColumnMap);
                break;

            default:
                throw new QueryError(\sprintf("Unsupported merge conflict mode: %s", (string) $mode));
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = 'returning ' . $this->formatReturning($return);
        }

        return \implode("\n", $output);
    }
}
