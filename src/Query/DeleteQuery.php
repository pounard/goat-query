<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Expression\TableExpression;
use Goat\Query\Partial\FromClauseTrait;
use Goat\Query\Partial\ReturningQueryTrait;
use Goat\Query\Partial\WhereClauseTrait;

/**
 * Represents an DELETE query.
 *
 * Here FROM clause trait represents the USING clause.
 */
final class DeleteQuery extends AbstractQuery
{
    use ReturningQueryTrait;
    use FromClauseTrait;
    use WhereClauseTrait;

    private TableExpression $table;

    /**
     * Build a new query.
     *
     * @param string|TableExpression $table
     *   SQL FROM clause table name.
     * @param string $alias
     *   Alias for FROM clause table.
     */
    public function __construct($table, ?string $alias = null)
    {
        $this->table = $this->normalizeStrictTable($table, $alias);
        $this->where = new Where();
    }

    /**
     * Get FROM table.
     */
    public function getTable(): TableExpression
    {
        return $this->table;
    }

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function getRelation(): ?TableExpression
    {
        @\trigger_error(\sprintf("%s is deprecated.", __METHOD__), E_USER_DEPRECATED);

        return $this->table;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->cloneWith();
        $this->cloneFrom();
        $this->table = clone $this->table;
        $this->where = clone $this->where;
    }
}
