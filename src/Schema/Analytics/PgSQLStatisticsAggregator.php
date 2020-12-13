<?php

declare(strict_types=1);

namespace Goat\Schema\Analytics;

use Goat\Runner\Runner;
use Goat\Schema\ColumnMetadata;

/**
 * Read table and columns statistics.
 *
 * @experimental
 */
final class PgSQLStatisticsAggregator
{
    private Runner $runner;

    public function __construct(Runner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * Get whole schema table statistics.
     */
    public function fetchSchemaStatistics(string $schema): array
    {
        $database = $this->runner->getSessionConfiguration()->getDatabase();

        return \iterator_to_array(
            $this
                ->runner
                ->execute(
                    <<<SQL
                    SELECT
                        pg_total_relation_size(s.relid) AS "sizeTotal",
                        pg_table_size(s.relid) AS "sizeTable",
                        pg_indexes_size(s.relid) AS "sizeIndex",
                        s.*,
                        c.reltuples AS row_count
                    FROM pg_stat_user_tables s
                    JOIN pg_class c
                        ON c.relname = s.relname
                        AND c.relnamespace = s.schemaname::regnamespace
                    WHERE
                        s.schemaname = ?
                    ORDER BY pg_total_relation_size(s.relid) ASC
                    SQL,
                    [$schema]
                )
                ->setHydrator($this->tableStatisticsHydrator($database))
        );
    }

    public function fetchTableStatistics(string $schema, string $table): PgSQLTableStatistics
    {
        $database = $this->runner->getSessionConfiguration()->getDatabase();
        $introspector = $this->runner->getPlatform()->createSchemaIntrospector($this->runner);

        $tableMetadata = $introspector->fetchTableMetadata($schema, $table);

        $ret = $this
            ->runner
            ->execute(
                <<<SQL
                SELECT
                    pg_total_relation_size(s.relid) AS "sizeTotal",
                    pg_table_size(s.relid) AS "sizeTable",
                    pg_indexes_size(s.relid) AS "sizeIndex",
                    s.*,
                    c.reltuples AS row_count
                FROM pg_stat_user_tables s
                JOIN pg_class c
                    ON c.relname = s.relname
                    AND c.relnamespace = s.schemaname::regnamespace
                WHERE
                    s.schemaname = ?
                    AND s.relname = ?
                SQL,
                [$schema, $database]
            )
            ->setHydrator($this->tableStatisticsHydrator($database))
            ->fetch()
        ;

        \assert($ret instanceof PgSQLTableStatistics);

        $escaper = $this->runner->getPlatform()->getEscaper();
        $escapedTableName = $escaper->escapeIdentifier($table->getSchema()) . '.' . $escaper->escapeIdentifier($table->getName());

        foreach ($tableMetadata->getColumns() as $column) {
            \assert($column instanceof ColumnMetadata);

            $columnStats = new PgSQLColumnStatistics(
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

            $ret->columns[] = $columnStats;
        }

        // Sort by size asc.
        \usort(
            $ret->columns,
            fn (PgSQLColumnStatistics $a, PgSQLColumnStatistics $b) => $a->sizeTotal - $b->sizeTotal
        );

        return $ret;
    }

    private function tableStatisticsHydrator(string $database): callable
    {
        return static function (array $row) use ($database) {
            $ret = new PgSQLTableStatistics(
                $database,
                $row['schemaname'],
                $row['relname']
            );

            $ret->sizeIndex = $row['sizeIndex'];
            $ret->sizeTable = $row['sizeTable'];
            $ret->sizeTotal = $row['sizeTotal'];
            $ret->rowCount = (int) $row['row_count'];

            $ret->readIndexScans = $row['idx_scan'];
            $ret->readIndexTupFetches = $row['idx_tup_fetch'];
            $ret->readSeqScans = $row['seq_scan'];
            $ret->readSeqTupReads = $row['seq_tup_read'];

            $ret->writeDeletes = $row['n_tup_del'];
            $ret->writeHotUpdates = $row['n_tup_hot_upd'];
            $ret->writeInserts = $row['n_tup_ins'];
            $ret->writeUpdates = $row['n_tup_upd'];

            $ret->stateLive = $row['n_live_tup'];
            $ret->stateDead = $row['n_dead_tup'];
            $ret->stateModSinceAnalyze = $row['n_mod_since_analyze'];

            $ret->analyzeCount = $row['analyze_count'];
            $ret->analyzeLast = $row['last_analyze'];
            $ret->vacuumCount = $row['vacuum_count'];
            $ret->vacuumLast = $row['last_vacuum'];

            return $ret;
        };
    }
}
