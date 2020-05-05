<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\ExpressionRaw;
use Goat\Query\QueryError;

/**
 * Represents the RETURNING part of any query.
 */
trait ReturningQueryTrait
{
    /** @var Column[] */
    private $return = [];

    /**
     * Get select columns array
     *
     * @return Column[]
     */
    public function getAllReturn(): array
    {
        return $this->return;
    }

    /**
     * Remove everything from the current RETURNING clause
     */
    public function removeAllReturn(): self
    {
        $this->return = [];

        return $this;
    }

    /**
     * Add a column to RETURNING clause.
     *
     * @param string|\Goat\Query\Expression $expression
     *   SQL select column
     * @param string $alias
     *   If alias to be different from the column
     */
    public function returning($expression = null, ?string $alias = null): self
    {
        if (!$expression) {
            if ($alias) {
                throw new QueryError("RETURNING * cannot be aliased.");
            }
            $expression = ExpressionRaw::create('*');
        }

        $this->return[] = Column::name($expression, $alias);

        return $this;
    }

    /**
     * Add an expression to RETURNING clause.
     *
     * @param string|\Goat\Query\Expression $expression
     *   SQL select column
     * @param string $alias
     *   If alias to be different from the column
     */
    public function returningExpression($expression, ?string $alias = null): self
    {
        $this->return[] = Column::expression($expression, $alias);

        return $this;
    }

    /**
     * Find column index for given alias
     *
     * @param string $alias
     */
    private function findReturnIndex(string $alias): ?string
    {
        foreach ($this->return as $index => $data) {
            if ($data->alias === $alias) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Remove column from projection
     *
     * @param string $name
     */
    public function removeReturn(string $alias): self
    {
        $index = $this->findReturnIndex($alias);

        if (null !== $index) {
            unset($this->return[$index]);
        }

        return $this;
    }

    /**
     * Does this project have the given column
     *
     * @param string $name
     */
    public function hasReturn(string $alias): bool
    {
        return (bool)$this->findReturnIndex($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function willReturnRows(): bool
    {
        return !empty($this->return);
    }
}
