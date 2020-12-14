<?php

declare(strict_types=1);

namespace Goat\Schema\Analytics;

use Goat\Runner\Runner;
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

    /** @var PgSQLTableStatistics[] */
    private $tables = [];

    public function __construct(Runner $runner)
    {
        $this->runner = $runner;
    }

    public function onTable(Context $context, TableMetadata $table): void
    {
        $aggregator = new PgSQLStatisticsAggregator($this->runner);

        $this->tables[] =  $aggregator->fetchTableStatistics($table->getSchema(), $table->getName());
    }
}
