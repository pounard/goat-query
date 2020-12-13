<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Schema;

use Goat\Query\QueryError;
use Goat\Runner\Runner;
use Goat\Schema\SchemaIntrospector;
use Goat\Schema\TableMetadata;
use Goat\Schema\Implementation\DefaultColumnMetadata;
use Goat\Schema\Implementation\DefaultForeignKeyMetatadata;
use Goat\Schema\Implementation\DefaultKeyMetatadata;
use Goat\Schema\Implementation\DefaultTableMetadata;

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
                $row['is_nullable'] !== 'NO',
                null, // @todo collation,
                $row['character_maximum_length'],
                $row['numeric_precision'],
                $row['numeric_scale'],
                false, // @todo unsigned,
                false // @todo sequence
            );
        }

        $allKeyInfo = $this
            ->runner
            ->execute(
                <<<SQL
                SELECT
                    con.conname AS name,
                    class_src.relname AS table_source,
                    (
                        SELECT nspname
                        FROM pg_catalog.pg_namespace
                        WHERE
                            oid = class_src.relnamespace
                    ) AS table_source_schema,
                    class_tgt.relname AS table_target,
                    (
                        SELECT nspname
                        FROM pg_catalog.pg_namespace
                        WHERE
                            oid = class_tgt.relnamespace
                    ) AS table_target_schema,
                    (
                        SELECT array_agg(attname)
                        FROM pg_catalog.pg_attribute
                        WHERE
                            attrelid = con.conrelid
                            AND attnum IN (SELECT * FROM unnest(con.conkey))
                    ) AS column_source,
                    (
                        SELECT array_agg(attname)
                        FROM pg_catalog.pg_attribute
                        WHERE
                            attrelid = con.confrelid
                            AND attnum IN (SELECT * FROM unnest(con.confkey))
                    ) AS column_target,
                    con.contype AS type
                FROM pg_catalog.pg_constraint con
                JOIN pg_catalog.pg_class class_src
                    ON class_src.oid = con.conrelid
                LEFT JOIN pg_catalog.pg_class class_tgt
                    ON class_tgt.oid = con.confrelid
                WHERE
                    con.contype IN ('f', 'p')
                    AND con.connamespace =  to_regnamespace(?)
                    AND (
                        con.conrelid =  to_regclass(?)
                        OR con.confrelid =  to_regclass(?)
                    )
                SQL,
                [$schema, $name, $name]
            )
        ;

        $primaryKey = null;
        $foreignKeys = [];
        $reverseForeignKeys = [];

        foreach ($allKeyInfo as $row) {
            switch ($row['type']) {
                case 'f':
                    $key = new DefaultForeignKeyMetatadata(
                        $database,
                        $row['table_source_schema'],
                        $row['table_source'],
                        $row['name'],
                        null, // @todo comment
                        $row['column_source'],
                        $row['table_target_schema'],
                        $row['table_target'],
                        $row['column_target']
                    );
                    if ($key->getTable() === $name) {
                        $foreignKeys[] = $key;
                    } else {
                        $reverseForeignKeys[] = $key;
                    }
                    break;
                case 'p':
                    $primaryKey = new DefaultKeyMetatadata(
                        $database,
                        $row['table_source_schema'],
                        $row['table_source'],
                        $row['name'],
                        null, // @todo comment
                        $row['column_source'],
                    );
                    break;
            }
        }

        return new DefaultTableMetadata(
            $database,
            $schema,
            $name,
            null, // @todo comment
            $primaryKey,
            $columns,
            $foreignKeys,
            $reverseForeignKeys
        );
    }
}
