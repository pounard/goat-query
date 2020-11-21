<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\Expression;
use Goat\Query\QueryError;
use Goat\Query\Where;
use Goat\Query\Expression\ColumnExpression;

/**
 * Represents the HAVING part of any query.
 *
 * Don't forget to handle HAVING in __clone(), __construct() and getArguments().
 */
trait HavingClauseTrait
{
    private Where $having;

    /**
     * Get HAVING clause.
     */
    public function getHaving(): Where
    {
        return $this->having;
    }

    /**
     * Open OR statement with parenthesis.
     *
     * @param callable $callback
     *   First argument of callback is the nested Where instance.
     */
    public function havingOr(callable $callback): self
    {
        $this->having->or($callback);

        return $this;
    }

    /**
     * Open AND statement with parenthesis.
     *
     * @param callable $callback
     *   First argument of callback is the nested Where instance.
     */
    public function havingAnd(callable $callback): self
    {
        $this->having->and($callback);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @deprecated
     *   Use self::having() instead
     */
    public function havingCondition($column, $value, string $operator = Where::EQUAL): self
    {
        @\trigger_error(\sprintf("%d::havingCondition() is deprecated, use %d::having() instead.", __CLASS__, __CLASS__), E_USER_DEPRECATED);

        $this->having->condition($column, $value, $operator);

        return $this;
    }

    /**
     * Add a condition in the HAVING clause.
     *
     * Default HAVING clause uses AND predicate.
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed $value
     * @param string $operator
     */
    public function having($column, $value, string $operator = Where::EQUAL): self
    {
        if ($column instanceof Where) {
            if (null !== $value) {
                throw new QueryError(\sprintf("You cannot pass a %d instance to condition() method with a value", Where::class));
            }
            $this->having->expression($column);

            return $this;
        }

        $this->having->condition($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary statement to the HAVING clause.
     *
     * Default HAVING clause uses AND predicate.
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
}
