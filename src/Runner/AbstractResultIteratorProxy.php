<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Driver\Instrumentation\QueryProfiler;
use Goat\Query\QueryError;
use Goat\Runner\Metadata\ResultMetadata;

abstract class AbstractResultIteratorProxy implements ResultIterator, \IteratorAggregate
{
    private ?ResultIterator $decorated = null;

    public function __construct(?ResultIterator $decorated = null)
    {
        $this->decorated = $decorated;
    }

    /**
     * Override this method if do not use default constructor.
     */
    protected function getResult(): ResultIterator
    {
        if ($this->decorated) {
            return $this->decorated;
        }

        throw new QueryError("Result proxy has no decorated instance set.");
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryProfiler(): QueryProfiler
    {
        return $this->getResult()->getQueryProfiler();
    }

    /**
     * @internal
     *
     * This may break if your result iterator is not an AbstractResultIterator
     */
    public function setQueryProfiler(QueryProfiler $profiler): void
    {
        $this->getResult()->setQueryProfiler($profiler);
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug(bool $enable): void
    {
        $this->getResult()->setDebug($enable);
    }

    /**
     * {@inheritdoc}
     */
    final public function setRewindable($rewindable = true): ResultIterator
    {
        $this->getResult()->setRewindable(true);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function setConverter(ConverterInterface $converter): ResultIterator
    {
        $this->getResult()->setConverter($converter);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function setHydrator(callable $hydrator): ResultIterator
    {
        $this->getResult()->setHydrator($hydrator);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function setKeyColumn(string $name): ResultIterator
    {
        $this->getResult()->setKeyColumn($name);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function setMetadata(array $userTypes, ?ResultMetadata $metadata = null): ResultIterator
    {
        $this->getResult()->setMetadata($userTypes, $metadata);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function count(): int
    {
        return $this->getResult()->countRows();
    }

    /**
     * {@inheritdoc}
     */
    final public function getIterator()
    {
        return $this->getResult();
    }

    /**
     * {@inheritdoc}
     */
    final public function countColumns(): int
    {
        return $this->getResult()->countColumns();
    }

    /**
     * {@inheritdoc}
     */
    final public function countRows(): int
    {
        return $this->getResult()->countRows();
    }

    /**
     * {@inheritdoc}
     */
    final public function columnExists(string $name): bool
    {
        return $this->getResult()->columnExists($name);
    }

    /**
     * {@inheritdoc}
     */
    final public function getColumnNames(): array
    {
        return $this->getResult()->getColumnNames();
    }

    /**
     * {@inheritdoc}
     */
    final public function getColumnTypes(): array
    {
        return $this->getResult()->getColumnTypes();
    }

    /**
     * {@inheritdoc}
     */
    final public function getColumnType(string $name): string
    {
        return $this->getResult()->getColumnType($name);
    }

    /**
     * {@inheritdoc}
     */
    final public function getColumnName(int $index): string
    {
        return $this->getResult()->getColumnName($index);
    }

    /**
     * {@inheritdoc}
     */
    final public function getColumnNumber(string $name): int
    {
        return $this->getResult()->getColumnNumber($name);
    }

    /**
     * {@inheritdoc}
     */
    final public function fetchField($name = null)
    {
        return $this->getResult()->fetchField($name);
    }

    /**
     * {@inheritdoc}
     */
    final public function fetchColumn($name = null)
    {
        return $this->getResult()->fetchColumn($name);
    }

    /**
     * {@inheritdoc}
     */
    final public function fetch()
    {
        return $this->getResult()->fetch();
    }
}
