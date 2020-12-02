<?php

declare(strict_types=1);

namespace Goat\Schema;

/**
 * Table metadata.
 *
 * This is an interface to allow implementations to generate code,
 * for performance purpose, it opens the door to very fast automatic
 * table based repositories.
 */
interface TableMetadata extends ObjectMetadata
{
    /**
     * Get primary key columns.
     *
     * Implementations should make this function really fast, it's one that
     * may be used at runtime for database operations.
     *
     * @return null|list<string>
     *   If null is returned, table has no primary key.
     */
    public function getPrimaryKey(): ?array;

    /**
     * Does this table have a primary key.
     */
    public function hasPrimaryKey(): bool;

    // @todo get foreign key associations

    /**
     * Get column type map.
     *
     * Implementations should make this function really fast, it's one that
     * may be used at runtime for database operations.
     *
     * @return array<string, string>
     *   Keys are column names, values are column types.
     *   This method does not account for nullable types.
     */
    public function getColumnTypeMap(): array;

    /**
     * Get all column metadata.
     *
     * @return array<string, ColumnMetadata>
     *   Keys are column names.
     */
    public function getColumns(): array;
}
