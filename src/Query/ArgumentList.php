<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Converter\ConverterInterface;

/**
 * Stores a parameter list along with types. It also stores the name map if any.
 */
class ArgumentList
{
    protected $index = 0;
    protected $nameMap = [];
    protected $names = [];
    protected $types = [];

    /**
     * Add a parameter
     *
     * @param string $type
     *   SQL datatype
     * @param string $name
     *   Named identifier, for query alteration to be possible
     *
     * @return int
     *   Added item position
     */
    final public function addParameter(?string $type = null, ?string $name = null): int
    {
        if ($name && isset($this->nameMap[$name])) {
            throw new QueryError(\sprintf("%s argument name is already in use in this query", $name));
        }

        $index = $this->index;
        $this->index++;

        $this->names[$index] = $name;
        $this->types[$index] = $type;

        if ($name) {
            $this->nameMap[$name] = $index;
        }

        return $index;
    }

    /**
     * Count items
     */
    final public function count(): int
    {
        return \count($this->types);
    }

    /**
     * Merge type information of the given argument list
     */
    final public function withTypesOf(ArgumentList $other): ArgumentList
    {
        if ($this->index !== $other->index) {
            throw new QueryError(\sprintf(
                "Length mismatch, awaiting %d arguments, got %d",
                $this->index, $other->index
            ));
        }

        $ret = clone $this;

        foreach ($other->types as $index => $type) {
            if ($type && ConverterInterface::TYPE_UNKNOWN !== $type) {
                $ret->types[$index] = $type;
            }
        }

        return $ret;
    }

    /**
     * Get type as array
     *
     * @return string[]
     *   Values are indexed positions (not names)
     */
    final public function getTypeMap(): array
    {
        return $this->types;
    }

    /**
     * Get datatype for given index
     */
    final public function getTypeAt(int $index): ?string
    {
        return $this->types[$index] ?? null;
    }

    /**
     * Raise name does not exist exception
     */
    private function nameDoesNotExist(string $name)
    {
        throw new QueryError(\sprintf("%s argument name does not exist", $name));
    }

    /**
     * Get name index
     */
    final public function getNameIndex(string $name): int
    {
        return $this->nameMap[$name] ?? $this->nameDoesNotExist($name);
    }
}
