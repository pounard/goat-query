<?php

declare(strict_types=1);

namespace Goat\Runner\Metadata;

use Goat\Runner\InvalidDataAccessError;

/**
 * Serves a default implementation for AbstractResultIterator and cached
 * column metadata.
 */
final class DefaultResultMetadata implements ResultMetadata
{
    private $count;
    private $names = [];
    private $sepyt = [];
    private $types = [];

    /**
     * Default constructor
     */
    public function __construct(array $names, array $types, ?int $count = null)
    {
        if ($count && $count !== \count($names)) {
            throw new InvalidDataAccessError(\sprintf("Column count (%d) and column names count (%d) mismatch", $count, \count($names)));
        }

        $this->count = $count;
        $this->names = $names;
        $this->types = $types;

        // It may look a bit silly knowing PHP extreme hash performance, but it
        // actually is a very efficient optimization.
        // Most calls will end up being the getColumnType() method, and this is
        // where optimization is good: we avoid doing a double array hash lookup
        // and do only one. It does matter.
        foreach ($names as $index => $name) {
            $this->sepyt[$name] = $types[$index];
        }
    }

    /**
     * Check index is valid
     */
    private function checkIndex(int $index): void
    {
        if ($index < 0) {
            throw new InvalidDataAccessError(\sprintf("Result column count start with 0: %d given", $index));
        }
        if (($count = $this->countColumns()) < $index + 1) {
            throw new InvalidDataAccessError(\sprintf("Result column count is %d: %d given", $count, $index));
        }
    }

    /**
     * Set column information
     */
    public function setColumnInformation(int $index, string $name, string $type)
    {
        $this->names[$index] = $name;
        $this->sepyt[$name] = $type;;
        $this->types[$index] = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames(): array
    {
        return $this->names;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnTypes(): array
    {
        return $this->types;
    }

    /**
     * {@inheritdoc}
     */
    public function columnExists(string $name): bool
    {
        return \in_array($name, $this->names);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType(string $name): ?string
    {
        return $this->sepyt[$name] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(int $index): string
    {
        $this->checkIndex($index);

        return $this->names[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function countColumns(): int
    {
        return $this->count ?? \count($this->names);
    }
}
