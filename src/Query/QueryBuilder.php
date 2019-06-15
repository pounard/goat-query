<?php

declare(strict_types=1);

namespace Goat\Query;

interface QueryBuilder
{
    /**
     * Creates and prepare a query
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
     * Create a select query builder
     *
     * @param null|string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     */
    public function select($relation = null, ?string $alias = null): SelectQuery;

    /**
     * Create an update query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     */
    public function update($relation, ?string $alias = null): UpdateQuery;

    /**
     * Create an insert query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     */
    public function insertValues($relation): InsertValuesQuery;

    /**
     * Create an insert with query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     */
    public function insertQuery($relation): InsertQueryQuery;

    /**
     * Create a delete query builder
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     */
    public function delete($relation, ?string $alias = null): DeleteQuery;
}
