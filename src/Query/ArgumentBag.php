<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Stores a copy of all parameters, and matching type if any found.
 *
 * Parameters are always an ordered array, they may not be identifier from
 * within the query, but they can be in this bag.
 */
class ArgumentBag extends ArgumentList
{
    private $data = [];

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
        if ($value instanceof ValueRepresentation) {
            if (!$type) {
                $type = $value->getType();
            }
            if (!$name) {
                $name = $value->getName();
            }
            $value = $value->getValue();
        }

        $index = parent::addParameter($type, $name);
        $this->data[$index] = $value;

        return $index;
    }

    /**
     * Get all values, returned index are either numerical or named.
     */
    public function getAll(): array
    {
        return $this->data;
    }

    /**
     * Append given bag vlues to this instance
     */
    public function append(ArgumentBag $bag): void
    {
        foreach ($bag->data as $index => $value) {
            $this->add($value, $bag->names[$index], $bag->types[$index]);
        }
    }

    /**
     * Append the given array to this instance
     */
    public function appendArray(array $array): void
    {
        foreach ($array as $index => $value) {
            if (\is_int($index)) {
                $this->add($value);
            } else {
                $this->add($value, index);
            }
        }
    }
}
