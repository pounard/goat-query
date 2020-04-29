<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Partial\FromClauseTrait;
use Goat\Query\Partial\ReturningQueryTrait;

/**
 * Represents an DELETE query
 */
final class DeleteQuery extends AbstractQuery
{
    use FromClauseTrait;
    use ReturningQueryTrait;

    private $where;

    /**
     * Build a new query
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     */
    public function __construct($relation, ?string $alias = null)
    {
        parent::__construct($relation, $alias);

        $this->where = new Where();
    }

    /**
     * Add a condition in the where clause
     *
     * @param string $columnName
     * @param mixed $value
     * @param string $operator
     */
    public function condition(string $column, $value, ?string $operator = Where::EQUAL): self
    {
        $this->where->condition($column, $value, $operator);

        return $this;
    }

    /**
     * Add an abitrary statement to the where clause
     *
     * @param string $statement
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
     * Get where statement
     */
    public function getWhere(): Where
    {
        return $this->where;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        $arguments = new ArgumentBag();

        foreach ($this->getAllWith() as $selectQuery) {
            $arguments->append($selectQuery[1]->getArguments());
        }

        foreach ($this->joins as $join) {
            $arguments->append($join->relation->getArguments());
            $arguments->append($join->condition->getArguments());
        }

        if (!$this->where->isEmpty()) {
            $arguments->append($this->where->getArguments());
        }

        return $arguments;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->cloneJoins();
        $this->where = clone $this->where;
    }
}
