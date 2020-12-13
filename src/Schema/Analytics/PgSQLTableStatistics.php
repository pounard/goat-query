<?php

declare(strict_types=1);

namespace Goat\Schema\Analytics;

use Goat\Schema\Browser\AbstractSchemaVisitor;

/**
 * @experimental
 */
final class PgSQLTableStatistics extends AbstractSchemaVisitor
{
    public string $database;
    public string $schema;
    public string $table;

    public ?int $sizeIndex = null;
    public ?int $sizeTable = null;
    public ?int $sizeTotal = null;
    public ?int $rowCount = null;

    public ?int $readIndexScans = null;
    public ?int $readIndexTupFetches = null;
    public ?int $readSeqScans = null;
    public ?int $readSeqTupReads = null;

    public ?int $writeDeletes = null;
    public ?int $writeHotUpdates = null;
    public ?int $writeInserts = null;
    public ?int $writeUpdates = null;

    public ?int $stateLive = null;
    public ?int $stateDead = null;
    public ?int $stateModSinceAnalyze = null;

    public ?int $analyzeCount = null;
    public ?\DateTimeInterface $analyzeLast = null;
    public ?int $vacuumCount = null;
    public ?\DateTimeInterface $vacuumLast = null;

    /** @var PgSQLColumnStatistics[] */
    public ?array $columns = null;

    public function __construct(string $database, string $schema, string $table)
    {
        $this->database = $database;
        $this->schema = $schema;
        $this->table = $table;
    }
}
