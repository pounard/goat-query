<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Partial\ReturningQueryTrait;

/**
 * Represents an INSERT INTO table SELECT ... query
 */
final class InsertQueryQuery extends Query
{
    use ReturningQueryTrait;

    private $columns = [];
    private $query;

    /**
     * Build a new query
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name, if you pass an ExpressionRelation
     *   instance from here, please note that its alias will be ignored, in
     *   SQL-92 standard INSERT relation cannot be aliased.
     */
    public function __construct($relation)
    {
        parent::__construct($relation);
    }

    /**
     * Get select columns array
     *
     * @return string[]
     */
    public function getAllColumns(): array
    {
        return $this->columns;
    }

    /**
     * Add columns
     *
     * @param string[] $columns
     *   List of columns names
     */
    public function columns(array $columns): self
    {
        if ($this->query) {
            throw new QueryError("once you added your query, you cannot change columns anymore");
        }

        $this->columns = \array_unique(\array_merge($this->columns, $columns));

        return $this;
    }

    /**
     * Get query
     */
    public function getQuery(): Query
    {
        if (!$this->query) {
            throw new QueryError("query has not been set yet");
        }

        return $this->query;
    }

    /**
     * Set SELECT query
     *
     * @param Query $query
     *   The query must return something
     */
    public function query(Query $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        return $this->query->getArguments();
    }
}
