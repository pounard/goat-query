<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\ExpressionColumn;
use Goat\Query\QueryError;

/**
 * Represents the RETURNING part of any query.
 */
trait ReturningQueryTrait
{
    private $return = [];

    /**
     * Get select columns array
     *
     * @return string[][]
     *   Values are arrays which contain:
     *     - first value: the column identifier (may contain the table alias
     *       or name with dot notation)
     *     - second value: the alias if any, or null
     */
    public function getAllReturn(): array
    {
        return $this->return;
    }

    /**
     * Remove everything from the current SELECT clause
     */
    public function removeAllReturn(): self
    {
        $this->return = [];

        return $this;
    }

    /**
     * Set or replace a column with a content.
     *
     * @param string|\Goat\Query\Expression $expression
     *   SQL select column
     * @param string $alias
     *   If alias to be different from the column
     */
    public function returning($expression = null, ?string $alias = null): self
    {
        if (!$expression) {
            $expression = '*';
        }
        if (!$alias) {
            if (!\is_string($expression) && !$expression instanceof ExpressionColumn) {
                throw new QueryError("RETURNING values can only be column names or expressions using them from the previous statement");
            }
            if (\is_string($expression)) {
                $expression = new ExpressionColumn($expression);
            }
        }

        $this->return[] = [$expression, $alias];

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
            if ($data[1] === $alias) {
                return $index;
            }
        }
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
