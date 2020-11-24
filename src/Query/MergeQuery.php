<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Expression\TableExpression;
use Goat\Query\Partial\InsertTrait;
use Goat\Query\Partial\MergeTrait;
use Goat\Query\Partial\ReturningQueryTrait;

/**
 * Represent either one of UPDATE .. ON CONFLICT DO .. or MERGE .. queries
 * depending upon your database implementation.
 */
class MergeQuery extends AbstractQuery
{
    use InsertTrait;
    use MergeTrait;
    use ReturningQueryTrait;

    private TableExpression $table;

    /**
     * Build a new query.
     *
     * @param string|TableExpression $table
     *   SQL INTO clause table name.
     * @param string $alias
     *   Alias for FROM clause table.
     */
    public function __construct($table, ?string $alias = null)
    {
        $this->table = $this->normalizeStrictTable($table, $alias);
    }

     /**
     * Get INTO table.
     */
    public function getTable(): TableExpression
    {
        return $this->table;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->cloneWith();
        $this->table = clone $this->table;
        $this->query = clone $this->query;
        $this->where = clone $this->where;
    }
}
