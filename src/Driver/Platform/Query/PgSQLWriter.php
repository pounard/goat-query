<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Query;

use Goat\Driver\Query\DefaultSqlWriter;
use Goat\Driver\Query\WriterContext;
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
    protected function doFormatInsertNoValuesStatement(WriterContext $context): string
    {
        return "DEFAULT VALUES";
    }

    /**
     * This is a copy-paste of formatQueryInsertValues(). In 2.x formatter will
     * be refactored to avoid such copy/paste.
     *
     * {@inheritdoc}
     */
    protected function formatQueryMerge(MergeQuery $query, WriterContext $context) : string
    {
        $output = [];

        $columns = $query->getAllColumns();

        if (!$table = $query->getTable()) {
            throw new QueryError("Insert query must have a table.");
        }

        $output[] = $this->doFormatWith($context, $query->getAllWith());
        // From SQL 92 standard, INSERT queries don't have table alias
        $output[] = 'insert into ' . $this->escaper->escapeIdentifier($table->getName());

        // @todo skip column names if numerical
        if ($columns) {
            $output[] = '(' . $this->doFormatColumnNameList($context, $columns) . ')';
        }

        $output[] = $this->format($query->getQuery(), $context);

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
                        $setColumnMap[$column] = new RawExpression("excluded." . $this->escaper->escapeIdentifier($column));
                    }
                }
                $output[] = 'on conflict (' . $this->doFormatColumnNameList($context, $key) . ')';
                $output[] = 'do update set';
                $output[] = $this->doFormatUpdateSet($context, $setColumnMap);
                break;

            default:
                throw new QueryError(\sprintf("Unsupported merge conflict mode: %s", (string) $mode));
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = 'returning ' . $this->doFormatReturning($context, $return);
        }

        return \implode("\n", $output);
    }
}
