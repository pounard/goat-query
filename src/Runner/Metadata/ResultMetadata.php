<?php

declare(strict_types=1);

namespace Goat\Runner\Metadata;

/**
 * @see ResultMetadataCache
 *   For more documentation about why this exists.
 */
interface ResultMetadata
{
    /**
     * Get the column count
     */
    public function countColumns(): int;

    /**
     * Does this column exists
     */
    public function columnExists(string $name): bool;

    /**
     * Get all column names, in select order
     *
     * @return string[]
     */
    public function getColumnNames(): array;

    /**
     * Get all column types, in select order
     *
     * @return string[]
     */
    public function getColumnTypes(): array;

    /**
     * Get column type
     */
    public function getColumnType(string $name): ?string;

    /**
     * Get column name
     */
    public function getColumnName(int $index): string;
}
