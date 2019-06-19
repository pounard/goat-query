<?php

declare(strict_types=1);

namespace Goat\Runner\Metadata;

/**
 * Keeps track of column metadata
 *
 * Rationale behind this is that the PDO driver has no optimisation behind
 * the PDO::getColumnMetadata() method, which is not expiremental anymore but
 * has been for many years. ext-pgsql is able to fetch data from the result
 * set, but PDO is not. Consequence is that each time you call the PDO
 * method, it will do SQL queries to the underlaying RDBMS server to fetch
 * type info, causing much performance trouble.
 *
 * If the developer using this API is able to tell if the SQL queries are
 * static when they are (meaning each page run will not end-up with different
 * select columns) then we may be able to cache permanently the result set
 * column types, and avoid useless database round trips.
 *
 * The ext-pgsql driver will not use this, only the PDO one. ext-pgsql is
 * both ultra stable and ultra fast, and doesn't need this kind of magic.
 */
interface ResultMetadataCache
{
    /**
     * Store a single query cache
     *
     * @param string $identifier
     *   Query identifier
     * @param string[] $names
     *   Keys are column indexes, values are column names
     * @param string[] $types
     *   Keys are column indexes, values are column types
     */
    public function store(string $identifier, array $names, array $types): void;

    /**
     * Fetch result set metadata
     *
     * @param string $identifier
     *   Query identifier
     */
    public function fetch(string $identifier): ?ResultMetadata;
}
