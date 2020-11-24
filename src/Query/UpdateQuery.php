<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Expression\TableExpression;
use Goat\Query\Partial\FromClauseTrait;
use Goat\Query\Partial\ReturningQueryTrait;
use Goat\Query\Partial\WhereClauseTrait;

/**
 * Represents an UPDATE query
 */
final class UpdateQuery extends AbstractQuery
{
    use FromClauseTrait;
    use ReturningQueryTrait;
    use WhereClauseTrait;

    private TableExpression $table;
    /** @var Expression|] */
    private array $columns = [];

    /**
     * Build a new query.
     *
     * @param string|Expression $table
     *   SQL FROM clause table name or expression.
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
     * Set a column value to update.
     *
     * @param string $columnName
     *   Must be, as the SQL-92 standard states, a single column name without
     *   the table prefix or alias, it cannot be an expression.
     * @param string|Statement|SelectQuery $expression
     *   The column value, if it's a string it can be a reference to any other
     *   field from the table or the FROM clause, as well as it can be raw
     *   SQL expression that returns only one row.
     *   Warning if a SelectQuery is passed here, it must return only one row
     *   else your database driver won't like it very much, and we cannot check
     *   this for you, since you could restrict the row count using WHERE
     *   conditions that matches the UPDATE table.
     */
    public function set(string $columnName, $expression): self
    {
        if (!\is_string($columnName) || false !== \strpos($columnName, '.')) {
            throw new QueryError("column names in the set part of an update query can only be a column name, without table prefix");
        }

        $this->columns[$columnName] = ExpressionFactory::value($expression);

        return $this;
    }

    /**
     * Set multiple column values to update.
     *
     * @param string[]|Expression[] $values
     *   Keys are column names, as specified in the ::value() method, and values
     *   are statements as specified by the same method.
     */
    public function sets(array $values): self
    {
        foreach ($values as $column => $statement) {
            $this->set($column, $statement);
        }

        return $this;
    }

    /**
     * Get all updated columns.
     *
     * @return string[]|Expression[]
     *   Keys are column names, values are either strings or Expression instances.
     */
    public function getUpdatedColumns(): array
    {
        return $this->columns;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->cloneWith();
        $this->cloneFrom();
        $this->table = clone $this->table;
        foreach ($this->columns as $column => $statement) {
            if (\is_object($statement)) {
                $this->columns[$column] = clone $statement;
            }
        }
        $this->where = clone $this->where;
    }
}
