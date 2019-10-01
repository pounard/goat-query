<?php

declare(strict_types=1);

namespace Goat\Runner\Tests;

use Goat\Runner\AbstractResultIterator;

/**
 * Iterate over an array with an arbitrary column definition
 */
final class ArrayResultIterator extends AbstractResultIterator
{
    /** @var string[][] */
    private $data;

    /** @var string[][] */
    private $definition;

    /**
     * Default constructor
     *
     * @param string[][] $definition
     *   Keys must be numeric, values are arrays whose first value is the
     *   the column name, second is the column type.
     */
    public function __construct(array $definition, array $data)
    {
        $this->data = $data;
        $this->definition = $definition;
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnInfoFromDriver(int $index): array
    {
        return $this->definition[$index];
    }

    /**
     * {@inheritdoc}
     */
    protected function countColumnsFromDriver(): int
    {
        return \count($this->definition);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($name = 0)
    {
        if (\is_int($name)) {
            $name = $this->getColumnName($name);
        }

        return \array_map(
            function ($row) use ($name) {
                return $this->convertValue($name, $row[$name] ?? null);
            },
            $this->data
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->data as $row) {
            yield $this->hydrate($row);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetch()
    {
        foreach ($this as $row) {
            return $row;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countRows(): int
    {
        return \count($this->data);
    }
}
