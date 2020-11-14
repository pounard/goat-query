<?php

declare(strict_types=1);

namespace Goat\Query;

interface QueryBuilder
{
    /**
     * Creates and prepare a query.
     *
     * Using this method, calling the same query more than once will only
     * format it once.
     *
     * Please note that re-executing the given query will imply that you give
     * the exact same number of arguments when executing it, which means that
     * you cannot use dynamic sized arrays are parameters.
     *
     * @param callable $callback
     *   This callback will receive the query builder as parameter, and must
     *   return any Query instance.
     * @param string $identifier
     *   In case the driver supports it, this will be propagated as the
     *   prepared query identifier.
     *
     * @return Query
     *   Returned object is immutable and cannot be altered anymore.
     */
    public function prepare(callable $callback, ?string $identifier = null): Query;

    /**
     * Create a SELECT query builder.
     *
     * @param null|string|Expression $table
     *   SQL FROM clause table name
     * @param string $alias
     *   Alias for FROM clause table
     */
    public function select($table = null, ?string $alias = null): SelectQuery;

    /**
     * Create an UPDATE query builder.
     *
     * @param string|Expression $table
     *   SQL FROM clause table name
     * @param string $alias
     *   Alias for FROM clause table
     */
    public function update($table, ?string $alias = null): UpdateQuery;

    /**
     * Create an INSERT query builder.
     *
     * @param string|Expression $table
     *   SQL FROM clause table name
     */
    public function insert($table): InsertQuery;

    /**
     * @deprecated
     * @see self::insert()
     */
    public function insertValues($table): InsertQuery;

    /**
     * @deprecated
     * @see self::insert()
     */
    public function insertQuery($table): InsertQuery;

    /**
     * Create an INSERT ... ON CONFLICT DO ... or MERGE ... query builder which
     * uses a constant table expression (i.e. VALUES (...), ...) as source.
     *
     * @param string|Expression $table
     *   SQL FROM clause table name
     */
    public function merge($table): MergeQuery;

    /**
     * @deprecated
     * @see self::insert()
     */
    public function upsertValues($table): MergeQuery;

    /**
     * @deprecated
     * @see self::insert()
     */
    public function upsertQuery($table): MergeQuery;

    /**
     * Create a DELETE query builder.
     *
     * @param string|Expression $table
     *   SQL FROM clause table name
     * @param string $alias
     *   Alias for FROM clause table
     */
    public function delete($table, ?string $alias = null): DeleteQuery;
}
