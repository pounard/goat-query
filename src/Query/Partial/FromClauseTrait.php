<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\ExpressionRaw;
use Goat\Query\Query;
use Goat\Query\Where;

/**
 * Represents the FROM part of a DELETE, SELECT or UPDATE query.
 *
 * It gathers all the FROM and JOIN statements altogether.
 *
 * For UPDATE queries, it is used for the FROM clause.
 * For DELETE queries, it is used for the USING clause.
 */
trait FromClauseTrait
{
    use AliasHolderTrait;

    /** @var Join[] */
    private $joins = [];

    /**
     * Get join clauses array
     *
     * @return array
     */
    final public function getAllJoin(): array
    {
        return $this->joins;
    }

    /**
     * Add join statement
     *
     * @param string|\Goat\Query\ExpressionRelation $relation
     * @param string|Where|ExpressionRaw $condition
     * @param string $alias
     * @param int $mode
     */
    final public function join($relation, $condition = null, ?string $alias = null, ?int $mode = Query::JOIN_INNER): self
    {
        $relation = $this->normalizeRelation($relation, $alias);

        $this->joins[] = new Join($relation, $condition, $mode);

        return $this;
    }

    /**
     * Add join statement and return the associated Where
     *
     * @param string|\Goat\Query\ExpressionRelation $relation
     * @param string $alias
     * @param int $mode
     */
    final public function joinWhere($relation, ?string $alias = null, ?int $mode = Query::JOIN_INNER): Where
    {
        $relation = $this->normalizeRelation($relation, $alias);

        $this->joins[] = new Join($relation, $condition = new Where(), $mode);

        return $condition;
    }

    /**
     * Add inner statement
     *
     * @param string|\Goat\Query\ExpressionRelation $relation
     * @param string|Where|ExpressionRaw $condition
     * @param string $alias
     */
    final public function innerJoin($relation, $condition = null, ?string $alias = null): self
    {
        $this->join($relation, $condition, $alias, Query::JOIN_INNER);

        return $this;
    }

    /**
     * Add left outer join statement
     *
     * @param string|\Goat\Query\ExpressionRelation $relation
     * @param string|Where|ExpressionRaw $condition
     * @param string $alias
     */
    final public function leftJoin($relation, $condition = null, ?string $alias = null): self
    {
        $this->join($relation, $condition, $alias, Query::JOIN_LEFT_OUTER);

        return $this;
    }

    /**
     * Add inner statement and return the associated Where
     *
     * @param string|\Goat\Query\ExpressionRelation $relation
     * @param string $alias
     */
    final public function innerJoinWhere($relation, string $alias = null): Where
    {
        return $this->joinWhere($relation, $alias, Query::JOIN_INNER);
    }

    /**
     * Add left outer join statement and return the associated Where
     *
     * @param string|\Goat\Query\ExpressionRelation $relation
     * @param string $alias
     */
    final public function leftJoinWhere($relation, string $alias = null): Where
    {
        return $this->joinWhere($relation, $alias, Query::JOIN_LEFT_OUTER);
    }

    /**
     * Deep clone support.
     */
    protected function cloneJoins()
    {
        foreach ($this->joins as $index => $join) {
            $this->joins[$index] = clone $join;
        }
    }
}
