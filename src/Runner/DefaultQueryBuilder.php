<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Query\DeleteQuery;
use Goat\Query\InsertQuery;
use Goat\Query\MergeQuery;
use Goat\Query\PreparedQuery;
use Goat\Query\Query;
use Goat\Query\QueryBuilder;
use Goat\Query\SelectQuery;
use Goat\Query\UpdateQuery;

class DefaultQueryBuilder implements QueryBuilder
{
    private $runner;

    public function __construct(?Runner $runner = null)
    {
        $this->runner = $runner;
    }

    private function setQueryRunner(Query $query): Query
    {
        if ($this->runner) {
            $query->setRunner($this->runner);
        }
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(callable $callback, ?string $identifier = null): Query
    {
        return new PreparedQuery(
            $this->runner,
            function () use ($callback) {
                return \call_user_func($callback, $this);
            },
            $identifier
        );
    }

    /**
     * {@inheritdoc}
     */
    final public function select($relation = null, ?string $alias = null): SelectQuery
    {
        return $this->setQueryRunner(new SelectQuery($relation, $alias));
    }

    /**
     * {@inheritdoc}
     */
    final public function update($relation, ?string $alias = null): UpdateQuery
    {
        return $this->setQueryRunner(new UpdateQuery($relation, $alias));
    }

    /**
     * {@inheritdoc}
     */
    public function insert($relation): InsertQuery
    {
        return $this->setQueryRunner(new InsertQuery($relation));
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     * @see self::insert()
     * @todo trigger deprecation notice
     */
    final public function insertQuery($relation): InsertQuery
    {
        return $this->insert($relation);
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     * @see self::insert()
     * @todo trigger deprecation notice
     */
    final public function insertValues($relation): InsertQuery
    {
        return $this->insert($relation);
    }

    /**
     * {@inheritdoc}
     */
    public function merge($relation): MergeQuery
    {
        return $this->setQueryRunner(new MergeQuery($relation));
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     * @see self::insert()
     * @todo trigger deprecation notice
     */
    public function upsertValues($relation): MergeQuery
    {
        return $this->merge($relation);
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     * @see self::insert()
     * @todo trigger deprecation notice
     */
    public function upsertQuery($relation): MergeQuery
    {
        return $this->merge($relation);
    }

    /**
     * {@inheritdoc}
     */
    final public function delete($relation, ?string $alias = null): DeleteQuery
    {
        return $this->setQueryRunner(new DeleteQuery($relation, $alias));
    }
}
