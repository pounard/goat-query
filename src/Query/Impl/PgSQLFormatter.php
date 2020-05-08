<?php

declare(strict_types=1);

namespace Goat\Query\Impl;

use Goat\Query\ExpressionRaw;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\UpsertQueryQuery;
use Goat\Query\UpsertValuesQuery;
use Goat\Query\Writer\DefaultFormatter;

/**
 * PostgreSQL >= 8.4 (untested before, althought it might work)
 */
class PgSQLFormatter extends DefaultFormatter
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
    protected function formatQueryUpsertValues(UpsertValuesQuery $query) : string
    {
        $output = [];

        $escaper = $this->escaper;
        $columns = $query->getAllColumns();
        $valueCount = $query->getValueCount();

        if (!$relation = $query->getRelation()) {
            throw new QueryError("insert query must have a relation");
        }

        // @todo move this elsewhere
        $output[] = $this->formatWith($query->getAllWith());

        $output[] = \sprintf(
            "insert into %s",
            // From SQL 92 standard, INSERT queries don't have table alias
            $this->escaper->escapeIdentifier($relation->getName())
        );

        if (!$valueCount) {
            // Assume there is no specific values, for PostgreSQL, we need to set
            // "DEFAULT VALUES" explicitely, for MySQL "() VALUES ()" will do the
            // trick
            $output[] = $this->formatInsertNoValuesStatement();

        } else {
            if ($columns) {
                $output[] = \sprintf(
                    "(%s) values",
                    \implode(', ', \array_map(function ($column) use ($escaper) {
                        return $escaper->escapeIdentifier($column);
                    }, $columns))
                );
            }

            $values = [];
            for ($i = 0; $i < $valueCount; ++$i) {
                $values[] = \sprintf(
                    "(%s)",
                    \implode(', ', \array_fill(0, \count($columns), '?'))
                );
            }
            $output[] = \implode(', ', $values);
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
                        $setColumnMap[$column] = ExpressionRaw::create("EXCLUDED." . $escaper->escapeIdentifier($column));
                    }
                }
                $output[] = "on conflict do update set";
                $output[] = $this->formatUpdateSet($setColumnMap);
                break;

            default:
                throw new QueryError(\sprintf("Unsupport upsert conflict mode: %s", (string) $mode));
        }

        // @todo move this elsewhere
        $return = $query->getAllReturn();
        if ($return) {
            $output[] = \sprintf("returning %s", $this->formatReturning($return));
        }

        return \implode("\n", $output);
    }

    /**
     * This is a copy-paste of formatQueryInsertFrom(). In 2.x formatter will
     * be refactored to avoid such copy/paste.
     *
     * {@inheritdoc}
     */
    protected function formatQueryUpsertQuery(UpsertQueryQuery $query) : string
    {
        $output = [];

        $escaper = $this->escaper;
        $columns = $query->getAllColumns();
        $subQuery = $query->getQuery();

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
            $output[] = \sprintf(
                "(%s)",
                \implode(', ', \array_map(function ($column) use ($escaper) {
                    return $escaper->escapeIdentifier($column);
                }, $columns))
            );
        }

        $output[] = $this->format($subQuery);

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
                        $setColumnMap[$column] = ExpressionRaw::create("EXCLUDED." . $escaper->escapeIdentifier($column));
                    }
                }
                $output[] = "on conflict do update set";
                $output[] = $this->formatUpdateSet($setColumnMap);
                break;

            default:
                throw new QueryError(\sprintf("Unsupport upsert conflict mode: %s", (string) $mode));
        }

        // @todo move this elsewhere
        $return = $query->getAllReturn();
        if ($return) {
            $output[] = \sprintf("returning %s", $this->formatReturning($return));
        }

        return \implode("\n", $output);
    }
}
