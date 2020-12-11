<?php

declare(strict_types=1);

namespace Goat\Schema\Analytics;

use Goat\Schema\Browser\AbstractSchemaVisitor;

/**
 * @experimental
 */
final class ColumnStatistics extends AbstractSchemaVisitor
{
    public string $database;
    public string $schema;
    public string $table;
    public string $column;

    public ?int $sizeTotal = null;
    public ?float $sizeAverage = null;
    public ?float $sizeTablePercent = null;

    public function __construct(string $database, string $schema, string $table, string $column)
    {
        $this->database = $database;
        $this->schema = $schema;
        $this->table = $table;
        $this->column = $column;
    }
}
