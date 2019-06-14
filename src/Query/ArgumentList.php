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
    public function addParameter(?string $type = null, ?string $name = null): int
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
    public function count(): int
    {
        return \count($this->types);
    }

    /**
     * Get type as array
     *
     * @return string[]
     *   Values are indexed positions (not names)
     */
    public function getTypeMap(?ArgumentList $other = null): array
    {
        $ret = $this->types;

        if ($other) {
            foreach ($other->types as $index => $type) {
                if (null !== $type && ConverterInterface::TYPE_UNKNOWN !== $type) {
                    $ret[$index] = $type;
                }
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
     * Raise name does not exist exception
     */
    private function nameDoesNotExist(string $name)
    {
        throw new QueryError(\sprintf("%s argument name does not exist", $name));
    }

    /**
     * Get name index
     */
    public function getNameIndex(string $name): int
    {
        return $this->nameMap[$name] ?? $this->nameDoesNotExist($name);
    }
}
