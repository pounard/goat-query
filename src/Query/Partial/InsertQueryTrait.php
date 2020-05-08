<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\Query;
use Goat\Query\QueryError;

/**
 * Represents an INSERT INTO table SELECT ... query.
 *
 * Suitable for both MERGE and INSERT queries.
 */
trait InsertQueryTrait
{
    private $columns = [];
    private $query;

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
}
