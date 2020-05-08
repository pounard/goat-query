<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\ArgumentBag;
use Goat\Query\QueryError;

/**
 * Represents an INTO table (...) VALUES (...) [, (...)].
 *
 * Suitable for both MERGE and INSERT queries.
 *
 * @todo, later remove the need for this, and use an ExpressionConstantTable
 *   instead, and extend the InsertQueryQuery for using the constant table
 *   expression as subquery.
 */
trait InsertValuesTrait
{
    private $arguments;
    private $columns = [];
    private $valueCount = 0;

    /**
     * Get select columns array
     *
     * @return string[]
     */
    public function getAllColumns(): array
    {
        return $this->columns;
    }

    /**
     * Add columns
     *
     * @param string[] $columns
     *   List of columns names
     */
    public function columns(array $columns): self
    {
        if ($this->valueCount) {
            throw new QueryError("once you added value, you cannot change columns anymore");
        }

        $this->columns = \array_unique(\array_merge($this->columns, $columns));

        return $this;
    }

    /**
     * Get all values
     */
    public function getValueCount(): int
    {
        return $this->valueCount;
    }

    /**
     * Add a set of values
     *
     * @param array $values
     *   Either values are numerically indexed, case in which they must match
     *   the internal columns order, or they can be key-value pairs case in
     *   which matching will be dynamically be done
     *
     * @todo
     *   - implement it correctly
     *   - allow arbitrary statement or subqueries?
     */
    public function values(array $values): self
    {
        if (!$this->columns) {
            $this->columns = \array_keys($values);
        }

        if (\count($values) !== \count($this->columns)) {
            throw new QueryError("values count does not match column count");
        }

        if (null === $this->arguments) {
            $this->arguments = new ArgumentBag();
        }

        foreach ($values as $value) {
            $this->arguments->add($value);
        }

        $this->valueCount++;

        return $this;
    }
}
