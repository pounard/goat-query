<?php

declare(strict_types=1);

namespace Goat\Schema;

/**
 * Service that introspects SQL schema.
 *
 * @todo (immediate)
 *   - cached implementation
 *   - php cache generator for repositories
 * @todo (long term)
 *   - view support?
 *   - foreign key constraints support?
 *   - write support?
 */
interface SchemaIntrospector
{
    /**
     * Get current database name.
     */
    public function getCurrentDatabase(): string;

    /**
     * List databases.
     *
     * @return string[]
     */
    public function listDatabases(): array;

    /**
     * List schemas in the current database.
     *
     * @return string[]
     */
    public function listSchemas(): array;

    /**
     * List tables in the current database.
     *
     * @return string[]
     */
    public function listTables(string $schema): array;

    /**
     * Get a single table metadata in the current database.
     */
    public function fetchTableMetadata(string $schema, string $name): TableMetadata;

    /**
     * Does table exists in the current database.
     */
    public function tableExists(string $schema, string $name): bool;
}
