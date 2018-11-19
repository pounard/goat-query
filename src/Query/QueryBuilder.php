<?php

declare(strict_types=1);

namespace Goat\Query;

interface QueryBuilder
{
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
