<?php

declare(strict_types=1);

namespace Goat\Schema\Analytics;

use Goat\Runner\Runner;
use Goat\Schema\ColumnMetadata;
use Goat\Schema\TableMetadata;
use Goat\Schema\Browser\AbstractSchemaVisitor;
use Goat\Schema\Browser\Context;

/**
 * React on tables, and give statistics for each one.
 *
 * @experimental
 */
final class PgSQLAnalyticsVisitor extends AbstractSchemaVisitor
{
    private Runner $runner;

    /** @var TableStatistics[] */
    private $tables = [];

    public function __construct(Runner $runner)
    {
        $this->runner = $runner;
    }

    public function onTable(Context $context, TableMetadata $table): void
    {
        $tableStats = new TableStatistics(
            $table->getDatabase(),
            $table->getSchema(),
            $table->getName()
        );

        $row = $this
            ->runner
            ->execute(
                <<<SQL
                SELECT
                    pg_total_relation_size(relid) AS "sizeTotal",
                    pg_table_size(relid) AS "sizeTable",
                    pg_indexes_size(relid) AS "sizeIndex",
                    *
                FROM pg_stat_user_tables
                WHERE
                    schemaname = ?
                    AND relname = ?
                SQL,
                [$table->getSchema(), $table->getName()]
            )
            ->fetch()
        ;

        if ($row) {
            $tableStats->sizeIndex = $row['sizeIndex'];
            $tableStats->sizeTable = $row['sizeTable'];
            $tableStats->sizeTotal = $row['sizeTotal'];

            $tableStats->readIndexScans = $row['idx_scan'];
            $tableStats->readIndexTupFetches = $row['idx_tup_fetch'];
            $tableStats->readSeqScans = $row['seq_scan'];
            $tableStats->readSeqTupReads = $row['seq_tup_read'];

            $tableStats->writeDeletes = $row['n_tup_del'];
            $tableStats->writeHotUpdates = $row['n_tup_hot_upd'];
            $tableStats->writeInserts = $row['n_tup_ins'];
            $tableStats->writeUpdates = $row['n_tup_upd'];

            $tableStats->stateLive = $row['n_live_tup'];
            $tableStats->stateDead = $row['n_dead_tup'];
            $tableStats->stateModSinceAnalyze = $row['n_mod_since_analyze'];

            $tableStats->analyzeCount = $row['analyze_count'];
            $tableStats->analyzeLast = $row['last_analyze'];
            $tableStats->vacuumCount = $row['vacuum_count'];
            $tableStats->vacuumLast = $row['last_vacuum'];
        }

        $escaper = $this->runner->getPlatform()->getEscaper();
        $escapedTableName = $escaper->escapeIdentifier($table->getSchema()) . '.' . $escaper->escapeIdentifier($table->getName());

        foreach ($table->getColumns() as $column) {
            \assert($column instanceof ColumnMetadata);

            $columnStats = new ColumnStatistics(
                $column->getDatabase(),
                $column->getSchema(),
                $column->getTable(),
                $column->getName()
            );

            $escapedColumnName = $escaper->escapeIdentifier($column->getName());

            $row = $this
                ->runner
                ->execute(
                    <<<SQL
                    SELECT
                        sum(pg_column_size({$escapedColumnName})) AS total_size,
                        avg(pg_column_size({$escapedColumnName})) AS average_size,
                        sum(pg_column_size({$escapedColumnName})) * 100.0 / pg_total_relation_size(?) AS percentage
                    FROM {$escapedTableName}
                    SQL,
                    [$escapedTableName]
                )
                ->fetch()
            ;

            if ($row) {
                $columnStats->sizeAverage = $row['average_size'];
                $columnStats->sizeTablePercent = $row['percentage'];
                $columnStats->sizeTotal = $row['total_size'];
            }

            $tableStats->columns[] = $columnStats;
        }
    }
}
