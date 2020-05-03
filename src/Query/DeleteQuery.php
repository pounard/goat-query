<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Partial\FromClauseTrait;
use Goat\Query\Partial\ReturningQueryTrait;
use Goat\Query\Partial\WhereClauseTrait;

/**
 * Represents an DELETE query
 */
final class DeleteQuery extends AbstractQuery
{
    use FromClauseTrait;
    use ReturningQueryTrait;
    use WhereClauseTrait;

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
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        $arguments = new ArgumentBag();

        foreach ($this->getAllWith() as $selectQuery) {
            $arguments->append($selectQuery[1]->getArguments());
        }

        foreach ($this->joins as $join) {
            $arguments->append($join[1]->getArguments());
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
