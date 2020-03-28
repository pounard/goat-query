<?php

declare(strict_types=1);

namespace Goat\Schema;

/**
 * Component able to introspect schema
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
     * Get namespaces (database or schemas)
     *
     * @return list<string>
     */
    public function listDatabases(): array;

    /**
     * Get namespaces (database or schemas)
     *
     * @return list<string>
     */
    public function listTablesIn(string $database): array;

    /**
     * Get a single table metadata
     *
     * If no schema provided use the default one in case of conflict. 
     */
    public function fetchTableMetadata(string $database, string $name, ?string $schema = null): TableMetadata;

    /**
     * Does table exists?
     */
    public function tableExists(string $database, string $name, ?string $schema = null): bool;
}
