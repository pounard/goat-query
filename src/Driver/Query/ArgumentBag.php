<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

use Goat\Query\QueryError;
use Goat\Query\ValueRepresentation;

/**
 * Stores a copy of all parameters, and matching type if any found.
 *
 * Parameters are always an ordered array, they may not be identifier from
 * within the query, but they can be in this bag.
 */
final class ArgumentBag
{
    private array $data = [];
    private array $nameMap = [];
    private array $names = [];
    private array $types = [];
    private int $index = 0;

    /**
     * Append the given array to this instance
     */
    public function addAll(iterable $array): void
    {
        foreach ($array as $index => $value) {
            if (\is_int($index)) {
                $this->add($value);
            } else {
                $this->add($value, $index);
            }
        }
    }

    /**
     * Add a parameter
     *
     * @param mixed $value
     *   Value
     * @param string $name
     *   Named identifier, for query alteration to be possible
     * @param string $type
     *   SQL datatype
     *
     * @return int
     *   Added item position
     */
    public function add($value, ?string $name = null, ?string $type = null): int
    {
        if ($name && isset($this->nameMap[$name])) {
            throw new QueryError(\sprintf("%s argument name is already in use in this query", $name));
        }

        if ($value instanceof ValueRepresentation) {
            if (!$type) {
                $type = $value->getType();
            }
            $value = $value->getValue();
        }

        $index = $this->index;
        $this->index++;

        $this->names[$index] = $name;
        $this->types[$index] = $type;

        if ($name) {
            $this->nameMap[$name] = $index;
        }

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
     * Get all values, returned index are either numerical or named.
     */
    public function getAll(): array
    {
        return $this->data;
    }

    /**
     * Get type as array.
     *
     * @return string[]
     *   Values are indexed positions (not names)
     */
    public function getTypeMap(): array
    {
        return $this->types;
    }

    /**
     * Get datatype for given index.
     */
    public function getTypeAt(int $index): ?string
    {
        return $this->types[$index] ?? null;
    }

    /**
     * Get name index.
     */
    public function getNameIndex(string $name): int
    {
        return $this->nameMap[$name] ?? $this->nameDoesNotExist($name);
    }

    /**
     * Raise name does not exist exception.
     */
    private function nameDoesNotExist(string $name)
    {
        throw new QueryError(\sprintf("%s argument name does not exist", $name));
    }
}
