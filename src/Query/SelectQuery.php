<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Partial\FromClauseTrait;

/**
 * Represents a SELECT query
 *
 * @todo
 *   - support a SelectQuery as FROM relation
 *   - implement __clone() once this done
 */
final class SelectQuery extends AbstractQuery implements Expression
{
    use FromClauseTrait;

    private $columns = [];
    private $forUpdate = false;
    private $groups = [];
    private $having;
    private $limit = 0;
    private $offset = 0;
    private $orders = [];
    private $performOnly = false;
    private $relation;
    private $relationAlias;
    private $where;

    /**
     * Build a new query
     *
     * @param null|string|ExpressionRelation $relation
     *   SQL from statement relation name, if null, select from no table
     * @param string $alias
     *   Alias for from clause relation
     */
    public function __construct($relation = null, ?string $alias = null)
    {
        parent::__construct($relation, $alias);

        $this->having = new Where();
        $this->where = new Where();
    }

    /**
     * Set the query as a SELECT ... FOR UPDATE query
     */
    public function forUpdate(): self
    {
        $this->forUpdate = true;

        return $this;
    }

    /**
     * Is this a SELECT ... FOR UPDATE
     */
    public function isForUpdate(): bool
    {
        return $this->forUpdate;
    }

    /**
     * Explicitely tell to the driver we don't want any return
     */
    public function performOnly(): self
    {
        $this->performOnly = true;

        return $this;
    }

    /**
     * Get select columns array
     */
    public function getAllColumns(): array
    {
        return $this->columns;
    }

    /**
     * Remove everything from the current SELECT clause
     */
    public function removeAllColumns(): self
    {
        $this->columns = [];

        return $this;
    }

    /**
     * Remove everything from the current ORDER clause
     */
    public function removeAllOrder(): SelectQuery
    {
        $this->orders = [];

        return $this;
    }

    /**
     * Add a selected column
     *
     * If you need to pass arguments, use a Expression instance or columnExpression().
     *
     * @param string|Expression $expression
     *   SQL select column
     * @param string
     *   If alias to be different from the column
     */
    public function column($expression, ?string $alias = null): self
    {
        $this->columns[] = [ExpressionFactory::column($expression), $alias];

        return $this;
    }

    /**
     * Add a selected column as a raw SQL expression
     *
     * @param string|Expression $expression
     *   SQL select column
     * @param string
     *   If alias to be different from the column
     * @param mixed[] $arguments
     *   Parameters for the arbitrary SQL
     */
    public function columnExpression($expression, ?string $alias = null, $arguments = []): self
    {
        if ($expression instanceof Expression) {
            if ($arguments) {
                throw new QueryError(\sprintf("you cannot call %s::columnExpression() and pass arguments if the given expression is not a string", __CLASS__));
            }
        } else {
            if ($arguments && !\is_array($arguments)) {
                $arguments = [$arguments];
            }
            $expression = ExpressionFactory::raw($expression, $arguments ?? []);
        }

        $this->columns[] = [$expression, $alias];

        return $this;
    }

    /**
     * Set or replace multiple columns at once
     *
     * @param string[] $columns
     *   Keys are aliases, values are SQL statements; if you do not wish to
     *   set aliases, keep the numeric indexes, if you want to use an integer
     *   as alias, just write it as a string, for example: "42".
     */
    public function columns(array $columns): self
    {
        foreach ($columns as $alias => $statement) {
            if (\is_int($alias)) {
                $this->column($statement);
            } else {
                $this->column($statement, $alias);
            }
        }

        return $this;
    }

    /**
     * Find column index for given alias
     */
    private function findColumnIndex(string $alias): int
    {
        foreach ($this->columns as $index => $data) {
            if ($data[1] === $alias) {
                return $index;
            }
        }
    }

    /**
     * Remove column from projection
     */
    public function removeColumn(string $alias): self
    {
        $index = $this->findColumnIndex($alias);

        if (null !== $index) {
            unset($this->columns[$index]);
        }

        return $this;
    }

    /**
     * Does this project have the given column
     */
    public function hasColumn(string $alias): bool
    {
        return (bool)$this->findColumnIndex($alias);
    }

    /**
     * Get group by clauses array
     */
    public function getAllGroupBy(): array
    {
        return $this->groups;
    }

    /**
     * Get order by clauses array
     */
    public function getAllOrderBy(): array
    {
        return $this->orders;
    }

    /**
     * Get query range
     *
     * @return int[]
     *   First value is limit second is offset
     */
    public function getRange(): array
    {
        return [$this->limit, $this->offset];
    }

    /**
     * Add a condition in the where clause
     *
     * @param string|ExpressionColumn $column
     * @param mixed $value
     * @param string $operator
     */
    public function condition($column, $value = null, string $operator = Where::EQUAL): self
    {
        if ($column instanceof Where) {
            if (null !== $value) {
                throw new QueryError(\sprintf("You cannot pass a %d instance to condition() method with a value", Where::class));
            }
            $this->where->expression($column);

            return $this;
        }

        $this->where->condition($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary statement to the where clause
     *
     * @param string|Expression $statement
     *   SQL string, which may contain parameters
     * @param mixed[] $arguments
     *   Parameters for the arbitrary SQL
     */
    public function expression($statement, $arguments = []): self
    {
        $this->where->expression($statement, $arguments);

        return $this;
    }

    /**
     * Alias of ::having()
     *
     * @codeCoverageIgnore
     * @deprecated
     *   Use self::having() instead
     */
    public function havingCondition($column, $value, string $operator = Where::EQUAL): self
    {
        \trigger_error(\sprintf("%s::%s is deprecated, use %s::having() instead", __CLASS__, __METHOD__, __CLASS__), E_USER_DEPRECATED);

        $this->having->condition($column, $value, $operator);

        return $this;
    }

    /**
     * Add a condition in the having clause
     *
     * @param string|ExpressionColumn $column
     * @param mixed $value
     * @param string $operator
     */
    public function having($column, $value, string $operator = Where::EQUAL): self
    {
        $this->having->condition($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary statement to the having clause
     *
     * @param string|Expression $statement
     *   SQL string, which may contain parameters
     * @param mixed[] $arguments
     *   Parameters for the arbitrary SQL
     */
    public function havingExpression($statement, $arguments = []): self
    {
        $this->having->expression($statement, $arguments);

        return $this;
    }

    /**
     * Get where statement
     */
    public function getWhere(): Where
    {
        return $this->where;
    }

    /**
     * Get having statement
     */
    public function getHaving(): Where
    {
        return $this->having;
    }

    /**
     * Add an order by clause
     *
     * @param string|Expression $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement
     * @param int $order
     *   One of the Query::ORDER_* constants
     * @param int $null
     *   Null behavior, nulls first, nulls last, or leave the backend default
     */
    public function orderBy($column, int $order = Query::ORDER_ASC, int $null = Query::NULL_IGNORE): self
    {
        if (!$column instanceof Expression) {
            $column = new ExpressionColumn($column);
        }

        $this->orders[] = [$column, $order, $null];

        return $this;
    }

    /**
     * Add an order by clause as a raw SQL expression
     *
     * @param string|Expression $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement
     * @param int $order
     *   One of the Query::ORDER_* constants
     * @param int $null
     *   Null behavior, nulls first, nulls last, or leave the backend default
     */
    public function orderByExpression($column, int $order = Query::ORDER_ASC, int $null = Query::NULL_IGNORE): self
    {
        if (!$column instanceof Expression) {
            $column = new ExpressionRaw($column);
        }

        $this->orders[] = [$column, $order, $null];

        return $this;
    }

    /**
     * Add a group by clause
     *
     * @param string|ExpressionColumn $column
     *   Column identifier must contain the table alias, if might be a raw SQL
     *   string if you wish, for example, to write a case when statement
     */
    public function groupBy($column): self
    {
        $this->groups[] = ExpressionFactory::column($column);

        return $this;
    }

    /**
     * Set limit/offset
     *
     * @param int $limit
     *   If empty or null, removes the current limit
     * @param int $offset
     *   If empty or null, removes the current offset
     */
    public function range(int $limit = 0, int $offset = 0): self
    {
        if (!\is_int($limit) || $limit < 0) {
            throw new QueryError(\sprintf("limit must be a positive integer: '%s' given", $limit));
        }
        if (!\is_int($offset) || $offset < 0) {
            throw new QueryError(\sprintf("offset must be a positive integer: '%s' given", $offset));
        }

        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Set limit/offset using a page number
     *
     * @param int $limit
     *   If empty or null, removes the current limit
     * @param int $page
     *   Page number, starts with one
     */
    public function page(int $limit = 0, int $page = 1): self
    {
        if (!\is_int($page) || $page < 1) {
            throw new QueryError(\sprintf("page must be a positive integer, starting with 1: '%s' given", $limit));
        }

        $this->range($limit, ($page - 1) * $limit);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        $arguments = new ArgumentBag();

        // WITH
        foreach ($this->getAllWith() as $selectQuery) {
            $arguments->append($selectQuery[1]->getArguments());
        }

        // SELECT
        foreach ($this->columns as $column) {
            if ($column[0] instanceof Statement) {
                $arguments->append($column[0]->getArguments());
            }
        }

        // JOIN
        foreach ($this->joins as $join) {
            $arguments->append($join->relation->getArguments());
            $arguments->append($join->condition->getArguments());
        }

        // WHERE
        if (!$this->where->isEmpty()) {
            $arguments->append($this->where->getArguments());
        }

        // GROUP BY
        foreach ($this->orders as $order) {
            if ($order[0] instanceof Statement) {
                $arguments->append($order[0]->getArguments());
            }
        }

        // HAVING
        if (!$this->having->isEmpty()) {
            $arguments->append($this->having->getArguments());
        }

        return $arguments;
    }

    /**
     * Get the count SelectQuery
     *
     * @param string $countAlias
     *   Alias of the count column
     *
     * @return SelectQuery
     *   Returned query will be a clone, the count row will be aliased with the
     *   given alias
     */
    public function getCountQuery(string $countAlias = 'count'): SelectQuery
    {
        // @todo do not remove necessary fields for group by and other
        //   aggregates functions (SQL standard)
        return (clone $this)
            ->setOption('class', null)
            ->setOption('hydrator', null)
            ->removeAllColumns()
            ->removeAllOrder()
            ->range(0, 0)
            ->column(ExpressionRaw::create("count(*)"), $countAlias)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function willReturnRows(): bool
    {
        return !$this->performOnly;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->cloneJoins();

        foreach ($this->columns as $index => $column) {
            $this->columns[$index][0] = clone $column[0];
        }

        foreach ($this->orders as $index => $order) {
            $this->orders[$index][0] = clone $order[0];
        }

        $this->where = clone $this->where;
        $this->having = clone $this->having;
    }
}
