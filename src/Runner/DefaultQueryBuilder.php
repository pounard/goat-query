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
    private ?Runner $runner = null;

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
    final public function select($table = null, ?string $alias = null): SelectQuery
    {
        return $this->setQueryRunner(new SelectQuery($table, $alias));
    }

    /**
     * {@inheritdoc}
     */
    final public function update($table, ?string $alias = null): UpdateQuery
    {
        return $this->setQueryRunner(new UpdateQuery($table, $alias));
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table): InsertQuery
    {
        return $this->setQueryRunner(new InsertQuery($table));
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     * @see self::insert()
     * @todo trigger deprecation notice
     */
    final public function insertQuery($table): InsertQuery
    {
        return $this->insert($table);
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     * @see self::insert()
     * @todo trigger deprecation notice
     */
    final public function insertValues($table): InsertQuery
    {
        return $this->insert($table);
    }

    /**
     * {@inheritdoc}
     */
    public function merge($table): MergeQuery
    {
        return $this->setQueryRunner(new MergeQuery($table));
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     * @see self::insert()
     * @todo trigger deprecation notice
     */
    public function upsertValues($table): MergeQuery
    {
        return $this->merge($table);
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     * @see self::insert()
     * @todo trigger deprecation notice
     */
    public function upsertQuery($table): MergeQuery
    {
        return $this->merge($table);
    }

    /**
     * {@inheritdoc}
     */
    final public function delete($table, ?string $alias = null): DeleteQuery
    {
        return $this->setQueryRunner(new DeleteQuery($table, $alias));
    }
}
