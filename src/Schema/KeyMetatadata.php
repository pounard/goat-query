<?php

declare(strict_types=1);

namespace Goat\Schema;

interface KeyMetatadata extends ObjectMetadata
{
    /**
     * Get table this key is on.
     */
    public function getTable(): string;

    /**
     * Get ordered columns names.
     */
    public function getColumnNames(): array;

    /**
     * Does this column appears in this key?
     */
    public function contains(string $columnName): bool;
}
