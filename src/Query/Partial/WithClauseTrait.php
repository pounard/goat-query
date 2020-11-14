<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\SelectQuery;

/**
 * Represents the WITH part of any query.
 */
trait WithClauseTrait
{
    /** @var With[] */
    private array $with = [];

    /**
     * Get WITH clauses array.
     *
     * @return array
     */
    final public function getAllWith(): array
    {
        return $this->with;
    }

    /**
     * Add with statement.
     */
    final public function with(string $alias, SelectQuery $select, bool $isRecursive = false): self
    {
        $this->with[] = new With($alias, $select, $isRecursive);

        return $this;
    }

    /**
     * Create new with statement.
     *
     * @param string $alias
     *    Alias for the with clause.
     * @param string|\Goat\Query\Expression $table
     *   SQL FROM clause table name.
     * @param string $tableAlias
     *   Alias for FROM clause table of the select query.
     * @param bool $isRecursive
     */
    final public function createWith(string $alias, $table, ?string $tableAlias = null, bool $isRecursive = false): SelectQuery
    {
        $select = new SelectQuery($table, $tableAlias);
        $this->with[] = new With($alias, $select, $isRecursive);

        return $select;
    }

    /**
     * Deep clone support.
     */
    protected function cloneWith()
    {
        foreach ($this->with as $index => $with) {
            $this->with[$index] = clone $with;
        }
    }
}
