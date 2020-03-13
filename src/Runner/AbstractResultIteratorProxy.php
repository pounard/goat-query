<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Hydrator\HydratorInterface;
use Goat\Runner\Metadata\ResultMetadata;
use Goat\Runner\Metadata\ResultProfile;

abstract class AbstractResultIteratorProxy implements ResultIterator
{
    private $count;

    abstract protected function getResult(): ResultIterator;

    /**
     * {@inheritdoc}
     */
    public function getResultProfile(): ResultProfile
    {
        return $this->getResult()->getResultProfile();
    }

    /**
     * @internal
     *
     * This may break if your result iterator is not an AbstractResultIterator
     */
    public function setResultProfile(ResultProfile $profile): void
    {
        $this->getResult()->setResultProfile($profile);
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
    final public function setConverter(ConverterInterface $converter): ResultIterator
    {
        $this->getResult()->setConverter($converter);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function setHydrator(HydratorInterface $hydrator): ResultIterator
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
