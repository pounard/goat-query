<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Schema;

use Goat\Runner\Runner;
use Goat\Schema\SchemaIntrospector;
use Goat\Schema\TableMetadata;
use Goat\Query\QueryError;
use Goat\Schema\DefaultColumnMetadata;
use Goat\Schema\DefaultTableMetadata;

/**
 * Please note that some functions here might use information_schema tables
 * which are restricted in listings, they will show up only table information
 * the current user owns or has non-SELECT privileges onto.
 */
class PgSQLSchemaIntrospector implements SchemaIntrospector
{
    private Runner $runner;

    public function __construct(Runner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentDatabase(): string
    {
        return $this
            ->runner
            ->getSessionConfiguration()
            ->getDatabase()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function listDatabases(): array
    {
        return $this
            ->runner
            ->execute(
                <<<SQL
                SELECT datname
                FROM pg_database
                ORDER BY datname ASC
                SQL
            )
            ->fetchColumn()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function listSchemas(): array
    {
        return $this
            ->runner
            ->execute(
                <<<SQL
                SELECT
                    schema_name
                FROM information_schema.schemata
                WHERE
                    catalog_name = ?
                    AND schema_name NOT LIKE 'pg\_%'
                    AND schema_name != 'information_schema'
                ORDER BY schema_name ASC
                SQL,
                [$this->getCurrentDatabase()]
            )
            ->fetchColumn()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function listTables(string $schema): array
    {
        return $this
            ->runner
            ->execute(
                <<<SQL
                SELECT
                    quote_ident(table_name) AS table_name
                FROM information_schema.tables
                WHERE
                    table_catalog = ?
                    AND table_schema = ?
                    AND table_name <> 'geometry_columns'
                    AND table_name <> 'spatial_ref_sys'
                    AND table_type <> 'VIEW'
                ORDER BY table_name ASC
                SQL,
                [$this->getCurrentDatabase(), $schema]
            )
            ->fetchColumn()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function tableExists(string $schema, string $name): bool
    {
        return (bool) $this
            ->runner
            ->execute(
                <<<SQL
                SELECT
                    true
                FROM information_schema.tables
                WHERE
                    table_catalog = ?
                    AND table_schema = ?
                    AND table_name = ?
                    AND table_type <> 'VIEW'
                SQL,
                [$this->getCurrentDatabase(), $schema, $name]
            )
            ->fetchColumn()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchTableMetadata(string $schema, string $name): TableMetadata
    {
        if (!$this->tableExists($schema, $name)) {
            throw new QueryError(\sprintf("Table %s.%s does not exist", $schema, $name));
        }

        $columns = [];
        $database = $this->getCurrentDatabase();

        $result = $this
            ->runner
            ->execute(
                <<<SQL
                SELECT
                    column_name,
                    data_type,
                    udt_name,
                    is_nullable,
                    -- collation
                    character_maximum_length,
                    numeric_precision,
                    numeric_scale
                    -- unsigned
                    -- sequence
                FROM information_schema.columns
                WHERE
                    table_catalog = ?
                    AND table_schema = ?
                    AND table_name = ?
                ORDER BY ordinal_position ASC
                SQL,
                [$database, $schema, $name]
            )
        ;

        foreach ($result as $row) {
            $columnName = $row['column_name'];

            $columns[$columnName] = new DefaultColumnMetadata(
                $database,
                $schema,
                $name,
                $columnName,
                null, // @todo comment
                $row['udt_name'],
                (bool) $row['is_nullable'] !== 'NO',
                null, // @todo collation,
                $row['character_maximum_length'],
                $row['numeric_precision'],
                $row['numeric_scale'],
                false, // @todo unsigned,
                false // @todo sequence
            );
        }

        $primaryKey = $this
            ->runner
            ->execute(
                <<<SQL
                SELECT
                    kcu.column_name
                FROM information_schema.key_column_usage kcu
                JOIN information_schema.table_constraints tc
                    ON tc.constraint_catalog = kcu.constraint_catalog
                    AND tc.constraint_schema = kcu.constraint_schema
                    AND tc.constraint_name = kcu.constraint_name
                JOIN information_schema.columns c
                    ON c.column_name = kcu.column_name
                    AND c.table_catalog = kcu.table_catalog
                    AND c.table_schema = kcu.table_schema
                    AND c.table_name = kcu.table_name
                WHERE
                    tc.constraint_type = 'PRIMARY KEY'
                    AND kcu.table_catalog = ?
                    AND kcu.table_schema = ?
                    AND kcu.table_name = ?
                SQL,
                [$database, $schema, $name]
            )
            ->fetchColumn()
        ;

        return new DefaultTableMetadata(
            $database,
            $schema,
            $name,
            null, // @todo comment
            $primaryKey,
            $columns
        );
    }
}
