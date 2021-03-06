<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterContext;
use Goat\Runner\Metadata\ResultMetadata;

/**
 * Empty iterator for some edge cases results
 */
final class EmptyResultIterator implements ResultIterator, \IteratorAggregate
{
    use WithQueryProfilerTrait;

    private int $affectedRowCount = 0;

    /**
     * Default constructor
     *
     * @param number $affectedRows
     */
    public function __construct(int $affectedRowCount = 0)
    {
        $this->affectedRowCount = $affectedRowCount;
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug(bool $enable): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setRewindable($rewindable = true): ResultIterator
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setConverterContext(ConverterContext $context): ResultIterator
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setHydrator(callable $hydrator): ResultIterator
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setKeyColumn(string $name): ResultIterator
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata(array $userTypes, ?ResultMetadata $metadata = null): ResultIterator
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \EmptyIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function countColumns(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function countRows(): int
    {
        return $this->affectedRowCount;
    }

    /**
     * {@inheritdoc}
     */
    public function columnExists(string $name): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnTypes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType(string $name): string
    {
        throw new InvalidDataAccessError("empty result cannot yield columns");
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(int $index): string
    {
        throw new InvalidDataAccessError("empty result cannot yield columns");
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNumber(string $name): int
    {
        throw new InvalidDataAccessError("empty result cannot yield columns");
    }

    /**
     * {@inheritdoc}
     */
    public function fetchField($name = null)
    {
        throw new InvalidDataAccessError("empty result cannot yield columns");
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($name = null)
    {
        throw new InvalidDataAccessError("empty result cannot yield columns");
    }

    /**
     * {@inheritdoc}
     */
    public function fetch()
    {
        return null;
    }
}
