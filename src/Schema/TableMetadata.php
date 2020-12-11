<?php

declare(strict_types=1);

namespace Goat\Schema;

/**
 * Table metadata.
 */
interface TableMetadata extends ObjectMetadata
{
    /**
     * Get primary key columns.
     *
     * Implementations should make this function really fast, it's one that
     * may be used at runtime for database operations.
     */
    public function getPrimaryKey(): ?KeyMetatadata;

    /**
     * Does this table have a primary key.
     */
    public function hasPrimaryKey(): bool;

    /**
     * Get foreign keys on this table referencing another tables.
     *
     * @return ForeignKeyMetatadata[]
     */
    public function getForeignKeys(): array;

    /**
     * Get foreign keys on another tables referencing this table.
     *
     * @return ForeignKeyMetatadata[]
     */
    public function getReverseForeignKeys(): array;

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
