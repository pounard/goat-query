<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\Expression;
use Goat\Query\ExpressionConstantTable;
use Goat\Query\Query;
use Goat\Query\QueryError;

/**
 * Handles values for INSERT and MERGE queries.
 */
trait InsertTrait
{
    private $query = null;
    private $queryIsConstantTable = false;
    private $columns = [];

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
        if ($this->columns) {
            throw new QueryError(\sprintf("You cannot set columns more than once of after calling %s::values().", __CLASS__));
        }

        $this->columns = \array_unique(\array_merge($this->columns, $columns));

        return $this;
    }

    /**
     * Get query.
     *
     * @return ExpressionConstantTable|Query
     */
    public function getQuery(): Expression
    {
        if (!$this->query) {
            throw new QueryError("Query was not set.");
        }

        return $this->query;
    }

    /**
     * Set SELECT or contant table expression query.
     *
     * @param Query $query
     *   The query must return something.
     */
    public function query(Expression $query): self
    {
        if ($this->query) {
            throw new QueryError(\sprintf("%s::query() was already set.", __CLASS__));
        }

        if (!$query instanceof Query) {
            if ($query instanceof ExpressionConstantTable) {
                $this->queryIsConstantTable = true;
            } else {
                throw new QueryError(\sprintf("Query must be a %s or %s instance.", Query::class, ExpressionConstantTable::class));
            }
        }

        $this->query = $query;

        return $this;
    }

    /**
     * Add a set of values.
     *
     * @param array $values
     *   Either values are numerically indexed, case in which they must match
     *   the internal columns order, or they can be key-value pairs case in
     *   which matching will be dynamically be done
     */
    public function values(array $values): self
    {
        if (null === $this->query) {
            $this->query = ExpressionConstantTable::create();
            $this->queryIsConstantTable = true;
        } else if (!$this->queryIsConstantTable) {
            throw new QueryError(\sprintf("%s::query() and %s::values() are mutually exclusive.", __CLASS__, __CLASS__));
        }

        if (!$this->columns) {
            $this->columns = \array_keys($values);
        }

        $this->query->row($values);

        return $this;
    }
}
