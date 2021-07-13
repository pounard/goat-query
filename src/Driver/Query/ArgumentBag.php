<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

use Goat\Query\Expression\ValueExpression;

/**
 * Stores a copy of all parameters, and matching type if any found.
 *
 * Parameters are always an ordered array, they may not be identifier from
 * within the query, but they can be in this bag.
 */
final class ArgumentBag
{
    private array $data = [];
    private array $types = [];
    private int $index = 0;

    /**
     * Append the given array to this instance
     */
    public function addAll(iterable $array): void
    {
        foreach ($array as $value) {
            $this->add($value);
        }
    }

    /**
     * Add a parameter
     *
     * @param mixed $value
     *   Value.
     * @param ?string $type
     *   SQL datatype.
     *
     * @return int
     *   Added item position.
     */
    public function add($value, ?string $type = null): int
    {
        if ($value instanceof ValueExpression) {
            if (!$type) {
                $type = $value->getType();
            }
            $value = $value->getValue();
        }

        $index = $this->index;
        $this->index++;

        $this->types[$index] = $type;
        $this->data[$index] = $value;

        return $index;
    }

    /**
     * Count items.
     */
    public function count(): int
    {
        return $this->index;
    }

    /**
     * Get all values.
     */
    public function getAll(): array
    {
        return $this->data;
    }

    /**
     * Get types.
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Set type at index if not set.
     */
    public function setTypeAt(int $index, ?string $type): void
    {
        if (!\array_key_exists($index, $this->types)) {
            $this->types[$index] = $type;
            // Avoid PHP warnings in certain circumstances.
            if (!\array_key_exists($index, $this->data)) {
                $this->data[$index] = null;
            }
        }
    }

    /**
     * Get datatype for given index.
     */
    public function getTypeAt(int $index): ?string
    {
        return $this->types[$index] ?? null;
    }
}
