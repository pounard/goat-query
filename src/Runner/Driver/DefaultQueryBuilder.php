<?php

declare(strict_types=1);

namespace Goat\Runner\Driver;

use Goat\Query\DeleteQuery;
use Goat\Query\InsertQueryQuery;
use Goat\Query\InsertValuesQuery;
use Goat\Query\PreparedQuery;
use Goat\Query\Query;
use Goat\Query\QueryBuilder;
use Goat\Query\SelectQuery;
use Goat\Query\UpdateQuery;
use Goat\Query\UpsertQueryQuery;
use Goat\Query\UpsertValuesQuery;
use Goat\Runner\Runner;

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
    final public function insertQuery($relation): InsertQueryQuery
    {
        return $this->setQueryRunner(new InsertQueryQuery($relation));
    }

    /**
     * {@inheritdoc}
     */
    final public function insertValues($relation): InsertValuesQuery
    {
        return $this->setQueryRunner(new InsertValuesQuery($relation));
    }

    /**
     * {@inheritdoc}
     */
    public function upsertValues($relation): UpsertValuesQuery
    {
        return $this->setQueryRunner(new UpsertValuesQuery($relation));
    }

    /**
     * {@inheritdoc}
     */
    public function upsertQuery($relation): UpsertQueryQuery
    {
        return $this->setQueryRunner(new UpsertQueryQuery($relation));
    }

    /**
     * {@inheritdoc}
     */
    final public function delete($relation, ?string $alias = null): DeleteQuery
    {
        return $this->setQueryRunner(new DeleteQuery($relation, $alias));
    }
}
