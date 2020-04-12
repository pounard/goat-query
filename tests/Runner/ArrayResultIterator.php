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
    /** @var int */
    private $fetchColumnCountCalls = 0;
    /** @var int */
    private $fetchRowCountCalls = 0;

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

    public function getFetchColumnCountCallCount(): int
    {
        return $this->fetchColumnCountCalls;
    }

    public function getFetchRowCountCallCount(): int
    {
        return $this->fetchRowCountCalls;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchColumnInfoFromDriver(int $index): array
    {
        return $this->definition[$index];
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchColumnsCountFromDriver(): int
    {
        ++$this->fetchColumnCountCalls;

        return \count($this->definition);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchNextRowFromDriver(): ?array
    {
        $row = \current($this->data);

        if (false === $row && null === \key($this->data)) {
            return null;
        }

        \next($this->data);

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchRowCountFromDriver(): int
    {
        ++$this->fetchRowCountCalls;

        return \count($this->data);
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
}
