<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\Expression;
use Goat\Query\Query;
use Goat\Query\Where;

/**
 * Represents the FROM part of a SELECT or UPDATE query.
 *
 * A FROM clause is (from PostgreSQL, but also a few other RDBMS):
 *
 *   - starts with a table name, with an alias or not,
 *   - possibly a comma-separated list of other table names, which can
 *     have aliases as well,
 *   - a list of JOIN statements.
 *
 * This means that all queries using this trait will have all of this.
 */
trait FromClauseTrait
{
    use AliasHolderTrait;

    /** @var Expression */
    private array $from = [];
    /** @var Join[] */
    private array $join = [];

    /**
     * Get join clauses array.
     *
     * @return Expression[]
     */
    final public function getAllFrom(): array
    {
        return $this->from;
    }

    /**
     * Add FROM table statement
     *
     * @param string|Expression $table
     * @param null|string $alias
     */
    final public function from($table, ?string $alias = null): self
    {
        $this->from[] = $this->normalizeTable($table, $alias);

        return $this;
    }

    /**
     * Get join clauses array.
     *
     * @return array
     */
    final public function getAllJoin(): array
    {
        return $this->join;
    }

    /**
     * Add join statement.
     *
     * @param string|Expression $table
     * @param string|Where|Expression $condition
     * @param string $alias
     * @param int $mode
     */
    final public function join($table, $condition = null, ?string $alias = null, ?int $mode = Query::JOIN_INNER): self
    {
        $table = $this->normalizeTable($table, $alias);

        $this->join[] = new Join($table, $condition, $mode);

        return $this;
    }

    /**
     * Add join statement and return the associated Where.
     *
     * @param string|\Goat\Query\Expression $table
     * @param string $alias
     * @param int $mode
     */
    final public function joinWhere($table, ?string $alias = null, ?int $mode = Query::JOIN_INNER): Where
    {
        $table = $this->normalizeTable($table, $alias);

        $this->join[] = new Join($table, $condition = new Where(), $mode);

        return $condition;
    }

    /**
     * Add inner statement.
     *
     * @param string|Expression $table
     * @param string|Where|Expression $condition
     * @param string $alias
     */
    final public function innerJoin($table, $condition = null, ?string $alias = null): self
    {
        $this->join($table, $condition, $alias, Query::JOIN_INNER);

        return $this;
    }

    /**
     * Add left outer join statement.
     *
     * @param string|Expression $table
     * @param string|Where|Expression $condition
     * @param string $alias
     */
    final public function leftJoin($table, $condition = null, ?string $alias = null): self
    {
        $this->join($table, $condition, $alias, Query::JOIN_LEFT_OUTER);

        return $this;
    }

    /**
     * Add inner statement and return the associated Where.
     *
     * @param string|Expression $table
     * @param string $alias
     */
    final public function innerJoinWhere($table, string $alias = null): Where
    {
        return $this->joinWhere($table, $alias, Query::JOIN_INNER);
    }

    /**
     * Add left outer join statement and return the associated Where.
     *
     * @param string|Expression $table
     * @param string $alias
     */
    final public function leftJoinWhere($table, string $alias = null): Where
    {
        return $this->joinWhere($table, $alias, Query::JOIN_LEFT_OUTER);
    }

    /**
     * Deep clone support.
     */
    protected function cloneFrom()
    {
        foreach ($this->from as $index => $expression) {
            $this->from[$index] = clone $expression;
        }
        foreach ($this->join as $index => $join) {
            $this->join[$index] = clone $join;
        }
    }
}
