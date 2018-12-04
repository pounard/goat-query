<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Hydrator\HydratorInterface;

abstract class AbstractResultIteratorProxy implements ResultIterator
{
    private $count;

    abstract protected function getResult(): ResultIterator;

    /**
     * {@inheritdoc}
     */
    final public function setConverter(ConverterInterface $converter): void
    {
        $this->getResult()->setConverter($converter);
    }

    /**
     * {@inheritdoc}
     */
    final public function setHydrator(HydratorInterface $hydrator): void
    {
        $this->getResult()->setHydrator($hydrator);
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
    final public function count()
    {
        return ($this->count ?? ($this->count = $this->getResult()->countRows()));
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
