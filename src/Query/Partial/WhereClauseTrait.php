<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\Expression;
use Goat\Query\QueryError;
use Goat\Query\Where;
use Goat\Query\Expression\ColumnExpression;

/**
 * Represents the WHERE part of any query.
 *
 * Don't forget to handle WHERE in __clone(), __construct().
 */
trait WhereClauseTrait
{
    private Where $where;

    /**
     * Get WHERE clause.
     */
    public function getWhere(): Where
    {
        return $this->where;
    }

    /**
     * Open OR statement with parenthesis.
     *
     * @param callable $callback
     *   First argument of callback is the nested Where instance.
     */
    public function whereOr(callable $callback): self
    {
        $this->where->or($callback);

        return $this;
    }

    /**
     * Open AND statement with parenthesis.
     *
     * @param callable $callback
     *   First argument of callback is the nested Where instance.
     */
    public function whereAnd(callable $callback): self
    {
        $this->where->and($callback);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @deprecated
     *   Please use self::where() instead.
     */
    public function condition($column, $value = null, string $operator = Where::EQUAL): self
    {
        @\trigger_error(\sprintf("%d::condition() is deprecated, use %d::where() instead.", __CLASS__, __CLASS__), E_USER_DEPRECATED);

        return $this->where($column, $value, $operator);
    }

    /**
     * Add a condition in the WHERE clause.
     *
     * Default WHERE clause uses AND predicate.
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed $value
     * @param string $operator
     */
    public function where($column, $value = null, string $operator = Where::EQUAL): self
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
     * @codeCoverageIgnore
     * @deprecated
     *   Please use self::whereExpression() instead.
     */
    public function expression($statement, $arguments = []): self
    {
        @\trigger_error(\sprintf("%d::expression() is deprecated, use %d::whereExpression() instead.", __CLASS__, __CLASS__), E_USER_DEPRECATED);

        return $this->whereExpression($statement, $arguments);
    }

    /**
     * Add an abitrary statement to the WHERE clause.
     *
     * Default WHERE clause uses AND predicate.
     *
     * @param string|Expression $statement
     *   SQL string, which may contain parameters
     * @param mixed[] $arguments
     *   Parameters for the arbitrary SQL
     */
    public function whereExpression($statement, $arguments = []): self
    {
        $this->where->expression($statement, $arguments);

        return $this;
    }
}
