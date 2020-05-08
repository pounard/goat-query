<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Query;

use Goat\Driver\Query\DefaultSqlWriter;
use Goat\Query\ExpressionConstantTable;
use Goat\Query\ExpressionRaw;
use Goat\Query\MergeQuery;
use Goat\Query\Query;
use Goat\Query\QueryError;

/**
 * PostgreSQL >= 8.4 (untested before, althought it might work)
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
        return \sprintf("%s::%s", $placeholder, $type);
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

        if (!$relation = $query->getRelation()) {
            throw new QueryError("insert query must have a relation");
        }

        $output[] = $this->formatWith($query->getAllWith());
        $output[] = \sprintf(
            "insert into %s",
            // From SQL 92 standard, INSERT queries don't have table alias
            $this->escaper->escapeIdentifier($relation->getName())
        );

        if ($columns) {
            $output[] = \sprintf("(%s)", $this->formatColumnNameList($columns));
        }

        $using = $query->getQuery();
        if ($using instanceof ExpressionConstantTable) {
            $output[] = $this->format($using);
        } else {
            $output[] = $this->format($using);
        }

        switch ($mode = $query->getConflictBehaviour()) {

            case Query::CONFLICT_IGNORE:
                // Do nothing.
                $output[] = "on conflict do nothing";
                break;

            case Query::CONFLICT_UPDATE:
                // Exclude primary key from the UPDATE statement.
                $key = $query->getKey();
                $setColumnMap = [];
                foreach ($columns as $column) {
                    if (!\in_array($column, $key)) {
                        $setColumnMap[$column] = ExpressionRaw::create("EXCLUDED." . $this->escaper->escapeIdentifier($column));
                    }
                }
                $output[] = "on conflict do update set";
                $output[] = $this->formatUpdateSet($setColumnMap);
                break;

            default:
                throw new QueryError(\sprintf("Unsupported merge conflict mode: %s", (string) $mode));
        }

        $return = $query->getAllReturn();
        if ($return) {
            $output[] = \sprintf("returning %s", $this->formatReturning($return));
        }

        return \implode("\n", $output);
    }
}
