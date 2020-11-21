<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Expression\ColumnExpression;
use Goat\Query\Expression\LikeExpression;

/**
 * Where represents the selection of the SQL query
 */
final class Where implements Statement
{
    const AND = 'and';
    const BETWEEN = 'between';
    const EQUAL = '=';
    const EXISTS = 'exists';
    const GREATER = '>';
    const GREATER_OR_EQUAL = '>=';
    const ILIKE = 'ilike';
    const IN = 'in';
    const IS_NULL = 'is null';
    const LESS = '<';
    const LESS_OR_EQUAL = '<=';
    const LIKE = 'like';
    const NOT_BETWEEN = 'not between';
    const NOT_EQUAL = '<>';
    const NOT_EXISTS = 'not exists';
    const NOT_ILIKE = 'not ilike';
    const NOT_IN = 'not in';
    const NOT_IS_NULL = 'is not null';
    const NOT_LIKE = 'not like';
    const OR = 'or';

    /**
     * @var ArgumentBag
     */
    protected $arguments;

    /**
     * @var string
     */
    protected $operator = self::AND;

    /**
     * @var Where
     */
    protected $parent;

    /**
     * @var WhereCondition[]
     */
    protected $conditions = [];

    /**
     * Default constructor
     *
     * @param string $operator
     *   Where::AND or Where::OR, determine which will be
     *   the operator inside this statement
     */
    public function __construct($operator = self::AND)
    {
        $this->operator = $operator;
    }

    /**
     * Is this statement empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->conditions);
    }

    /**
     * For internal use only
     *
     * @param Where $parent
     *
     * @return $this
     */
    protected function setParent(Where $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Reset internal cache if necessary
     */
    protected function reset()
    {
        // Never use unset() this will unassign the class property and make
        // PHP raise notices on further accesses.
        $this->arguments = null;
    }

    private function operatorNeedsValue(string $operator)
    {
        return $operator !== self::IS_NULL && $operator !== self::NOT_IS_NULL;
    }

    /**
     * Add a condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed|callable|Statement $value
     * @param string $operator
     *
     * @return $this
     */
    public function condition($column, $value = null, string $operator = self::EQUAL)
    {
        if (null === $value && \is_callable($column)) {
            return $this->expression($column);
        }

        $column = ExpressionFactory::column($column);

        if (!$this->operatorNeedsValue($operator)) {
            if ($value) {
                throw new QueryError(\sprintf("operator %s cannot carry a value", $operator));
            }
            $value = null;
        } else if (\is_array($value)) {
            foreach ($value as $index => $current) {
                $value[$index] = ExpressionFactory::value($current);
            }
        } else {
            $value = ExpressionFactory::value($value);
        }

        if (self::EQUAL === $operator) {
            if (\is_array($value) || $value instanceof SelectQuery) {
                $operator = self::IN;
            }
        } else if (self::NOT_EQUAL === $operator) {
            if (\is_array($value) || $value instanceof SelectQuery) {
                $operator = self::NOT_IN;
            }
        } else if (self::BETWEEN === $operator || self::NOT_BETWEEN === $operator) {
            if (!\is_array($value) || 2 !== \count($value)) {
                throw new QueryError("between and not between operators needs exactly 2 values");
            }
        }

        $this->conditions[] = new WhereCondition($column, $value, $operator);
        $this->reset();

        return $this;
    }

    /**
     * Add an abitrary SQL expression
     *
     * @param string|Expression $expression
     *   SQL string, which may contain parameters
     * @param mixed|mixed[] $arguments
     *   Parameters for the arbitrary SQL
     *
     * @return $this
     */
    public function expression($expression, $arguments = [])
    {
        if ($expression instanceof Where || $expression instanceof Expression) {
            if ($arguments) {
                throw new QueryError(\sprintf("you cannot call %s::expression() and pass arguments if the given expression is not a string", __CLASS__));
            }
        } else if (null !== $arguments) {
            if (!\is_array($arguments)) {
                $arguments = [$arguments];
            }
            if (null === ($expression = ExpressionFactory::raw($expression, $arguments, $this))) {
                return; // Callback took control.
            }
        } else {
            if (null === ($expression = ExpressionFactory::raw($expression, $arguments, $this))) {
                return; // Callback took control.
            }
        }

        $this->conditions[] = new WhereCondition(null, $expression, null);

        return $this;
    }

    /**
     * Add an exists condition
     */
    public function exists(SelectQuery $query)
    {
        $this->conditions[] = new WhereCondition(null, $query, self::EXISTS);

        return $this;
    }

    /**
     * Add an exists condition
     */
    public function notExists(SelectQuery $query)
    {
        $this->conditions[] = new WhereCondition(null, $query, self::NOT_EXISTS);

        return $this;
    }

    /**
     * Start a new parenthesis statement
     *
     * @param string $operator
     *   Where::OP_AND or Where::OP_OR, determine which will be the operator
     *   inside this where statement
     *
     * @return Where
     */
    public function open(string $operator = self::AND) : Where
    {
        $this->reset();

        $where = (new Where($operator))->setParent($this);
        $this->conditions[] = new WhereCondition(null, $where, null);

        return $where;
    }

    /**
     * End a previously started statement
     *
     * @return Where
     */
    public function close() : Where
    {
        if (!$this->parent) {
            throw new QueryError("cannot end a statement without a parent");
        }

        return $this->parent;
    }

    /**
     * '=' condition
     *
     * If value is an array, this will be converted to a 'in' condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed $value
     *
     * @return $this
     */
    public function isEqual($column, $value)
    {
        return $this->condition($column, $value, Where::EQUAL);
    }

    /**
     * '<>' condition
     *
     * If value is an array, this will be converted to a 'not in' condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed $value
     *
     * @return $this
     */
    public function isNotEqual($column, $value)
    {
        return $this->condition($column, $value, Where::NOT_EQUAL);
    }

    /**
     * 'like' condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed[] $values
     * @param bool $caseSensitive
     *
     * @return $this
     */
    public function isLike($column, string $pattern, ?string $value = null, ?string $wildcard = null)
    {
        return $this->expression(LikeExpression::like($column, $pattern, $value, $wildcard));
    }

    /**
     * 'not like' condition
     *
     * @param string|Expression $column
     *   Column, or expression that can be compared against, anything will do.
     * @param string $pattern
     *   Any string with % and _ inside, and  for value, use ? for value replacement.
     * @param ?string $value
     *   Any value to replace within pattern.
     * @param ?string $wildcard
     *   Wilcard if different, default is '?'.
     *
     * @return $this
     */
    public function isNotLike($column, string $pattern, ?string $value = null, ?string $wildcard = null)
    {
        return $this->expression(LikeExpression::notLike($column, $pattern, $value, $wildcard));
    }

    /**
     * 'ilike' condition
     *
     * @param string|Expression $column
     *   Column, or expression that can be compared against, anything will do.
     * @param string $pattern
     *   Any string with % and _ inside, and  for value, use ? for value replacement.
     * @param ?string $value
     *   Any value to replace within pattern.
     * @param ?string $wildcard
     *   Wilcard if different, default is '?'.
     *
     * @return $this
     */
    public function isLikeInsensitive($column, string $pattern, ?string $value = null, ?string $wildcard = null)
    {
        return $this->expression(LikeExpression::iLike($column, $pattern, $value, $wildcard));
    }

    /**
     * 'not ilike' condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed[] $values
     * @param bool $caseSensitive
     *
     * @return $this
     */
    public function isNotLikeInsensitive($column, string $pattern, ?string $value = null, ?string $wildcard = null)
    {
        return $this->expression(LikeExpression::notILike($column, $pattern, $value, $wildcard));
    }

    /**
     * 'in' condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed[] $values
     *
     * @return $this
     */
    public function isIn($column, $values)
    {
        return $this->condition($column, $values, Where::IN);
    }

    /**
     * 'not in' condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed[] $values
     *
     * @return $this
     */
    public function isNotIn($column, $values)
    {
        return $this->condition($column, $values, Where::NOT_IN);
    }

    /**
     * '>' condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed $value
     *
     * @return $this
     */
    public function isGreater($column, $value)
    {
        return $this->condition($column, $value, Where::GREATER);
    }

    /**
     * '<' condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed $value
     *
     * @return $this
     */
    public function isLess($column, $value)
    {
        return $this->condition($column, $value, Where::LESS);
    }

    /**
     * '>=' condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed $value
     *
     * @return $this
     */
    public function isGreaterOrEqual($column, $value)
    {
        return $this->condition($column, $value, Where::GREATER_OR_EQUAL);
    }

    /**
     * '<=' condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed $value
     *
     * @return $this
     */
    public function isLessOrEqual($column, $value)
    {
        return $this->condition($column, $value, Where::LESS_OR_EQUAL);
    }

    /**
     * 'between' condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed $from
     * @param mixed $to
     *
     * @return $this
     */
    public function isBetween($colunm, $from, $to)
    {
        return $this->condition($colunm, [$from, $to], Where::BETWEEN);
    }

    /**
     * 'not between' condition
     *
     * @param string|Expression|ColumnExpression $column
     * @param mixed $from
     * @param mixed $to
     *
     * @return $this
     */
    public function isNotBetween($colunm, $from, $to)
    {
        return $this->condition($colunm, [$from, $to], Where::NOT_BETWEEN);
    }

    /**
     * Add an is null condition
     *
     * @param string|Expression|ColumnExpression $column
     *
     * @return $this
     */
    public function isNull($column)
    {
        return $this->condition($column, null, Where::IS_NULL);
    }

    /**
     * Add an is not null condition
     *
     * @param string|Expression|ColumnExpression $column
     *
     * @return $this
     */
    public function isNotNull($column)
    {
        return $this->condition($column, null, Where::NOT_IS_NULL);
    }

    /**
     * Open an and clause.
     *
     * @param ?callable $callback
     *   First argument of callback is the nested Where instance.
     *
     * @return Where
     */
    public function and(?callable $callback = null) : Where
    {
        $nestedWhere = $this->open(Where::AND);

        if ($callback) {
            $callback($nestedWhere);
        }

        return $nestedWhere;
    }

    /**
     * Open an or clause.
     *
     * @param ?callable $callback
     *   First argument of callback is the nested Where instance.
     *
     * @return Where
     */
    public function or(?callable $callback = null) : Where
    {
        $nestedWhere = $this->open(Where::OR);

        if ($callback) {
            $callback($nestedWhere);
        }

        return $nestedWhere;
    }

    /**
     * Alias of ::close()
     *
     * @return Where
     */
    public function end() : Where
    {
        return $this->close();
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments() : ArgumentBag
    {
        if (null !== $this->arguments) {
            return $this->arguments;
        }

        $arguments = new ArgumentBag();

        /** @var \Goat\Query\WhereCondition $condition */
        foreach ($this->conditions as $condition) {
            if ($condition->value instanceof Statement) {
                $arguments->append($condition->value->getArguments());
            } else {
                switch ($condition->operator) {

                    case Where::IS_NULL:
                    case Where::NOT_IS_NULL:
                        break;

                    default:
                        // This is ugly as hell, fix me.
                        foreach ((array)$condition->value as $candidate) {
                            if ($candidate instanceof Statement) {
                                $arguments->append($candidate->getArguments());
                            } else {
                                $arguments->add($candidate);
                            }
                        }
                        break;
                }
            }
        }

        return $this->arguments = $arguments;
    }

    /**
     * Get operator
     *
     * @return string
     */
    public function getOperator() : string
    {
        return $this->operator;
    }

    /**
     * Get conditions
     *
     * @return WhereCondition[]
     */
    public function getConditions() : array
    {
        return $this->conditions;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->reset();

        foreach ($this->conditions as $index => $condition) {
            $this->conditions[$index] = clone $condition;
        }
    }
}

final class WhereCondition
{
    /** @var null|Statement */
    public $column;

    /** @var string|Statement */
    public $operator;

    /** @var null|string */
    public $value;

    public function __construct($column, $value, ?string $operator = null)
    {
        $this->column = $column;
        $this->value = $value;
        $this->operator = $operator;
    }
}
