<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Stores a copy of all parameters, and matching type if any found.
 *
 * Parameters are always an ordered array, they may not be identifier from
 * within the query, but they can be in this bag.
 */
class ArgumentBag
{
    private $data = [];
    private $frozen = false;
    private $index = 0;
    private $nameMap = [];
    private $names = [];
    private $types = [];

    /**
     * Lock this instance
     */
    public function lock(): void
    {
        $this->frozen = true;
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
     */
    public function add($value, ?string $name = null, ?string $type = null): void
    {
        if ($this->frozen) {
            throw new QueryError(\sprintf("You cannot call %s::add() object is frozen", self::class));
        }
        if ($name && isset($this->nameMap[$name])) {
            throw new QueryError(\sprintf("%s argument name is already in use in this query", $name));
        }

        $index = $this->index++;

        $this->data[$index] = $value;
        $this->names[$index] = $name;
        $this->types[$index] = $type;

        if ($name) {
            $this->nameMap[$name] = $index;
        }
    }

    /**
     * Get all values, returned index are either numerical or named.
     */
    public function getAll(): array
    {
        return $this->data;
    }

    /**
     * Merge with given values and return a new instance
     *
     * @param mixed[]|self
     */
    public function merge($arguments): self
    {
        if ($arguments instanceof self) {
            $arguments = $arguments->data;
        } else if (!\is_array($arguments)) {
            throw new QueryError(\sprintf("%s::merge() parameter can only be an instance of %s or an array", self::class, self::class));
        }

        /** @var \Goat\Query\ArgumentBag $ret */
        $ret = clone $this;

        if ($arguments) {
            foreach ($arguments as $name => $value) {
                if (\is_int($name)) {
                    $index = $name;
                } else if (!isset($ret->nameMap[$name])) {
                    throw new QueryError(\sprintf("named argument %s does not exist in the current query", $name));
                } else {
                    $index = $this->nameMap[$name];
                }
                $ret->data[$index] = $value;
            }
        }

        return $ret;
    }

    /**
     * Get datatype for given index
     */
    public function getTypeAt(int $index): ?string
    {
        return $this->types[$index] ?? null;
    }

    /**
     * Append given bag vlues to this instance
     */
    public function append(ArgumentBag $bag): void
    {
        if ($this->frozen) {
            throw new QueryError(\sprintf("You cannot call %s::append() object is frozen", self::class));
        }

        foreach ($bag->data as $index => $value) {
            $this->add($value, $bag->names[$index], $bag->types[$index]);
        }
    }

    /**
     * Append the given array to this instance
     */
    public function appendArray(array $array): void
    {
        if ($this->frozen) {
            throw new QueryError(\sprintf("You cannot call %s::appendArray() object is frozen", self::class));
        }

        foreach ($array as $index => $value) {
            if (\is_int($index)) {
                $this->add($value);
            } else {
                $this->add($value, $index);
            }
        }
    }
}
