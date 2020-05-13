<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Driver\Platform\Platform;
use Goat\Driver\Runner\AbstractRunner;
use Goat\Query\QueryBuilder;
use Goat\Runner\Hydrator\HydratorRegistry;
use Goat\Runner\Metadata\ResultMetadataCache;
use Psr\Log\LoggerInterface;

/**
 * @todo
 *   All methods that are not defined in interface but defined on the
 *   AbstractRunner must be re-implemented propertly: they all target side
 *   features, nevertheless required features, live but where the software
 *   design is wrong.
 */
abstract class AbstractRunnerProxy implements Runner
{
    private Runner $decorated;

    public function __construct(Runner $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * Set logger.
     *
     * @deprecated
     */
    public function setLogger(LoggerInterface $logger): void
    {
        if ($this->decorated instanceof AbstractRunner) {
            $this->decorated->setLogger($logger);
        }
    }

    /**
     * Toggle debug mode.
     *
     * @deprecated
     */
    public function setDebug(bool $value): void
    {
        if ($this->decorated instanceof AbstractRunner) {
            $this->decorated->setDebug($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setResultMetadataCache(ResultMetadataCache $metadataCache): void
    {
        $this->decorated->setResultMetadataCache($metadataCache);
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter): void
    {
        $this->decorated->setConverter($converter);
    }

    /**
     * {@inheritdoc}
     */
    final public function setHydratorRegistry(HydratorRegistry $hydratorRegistry): void
    {
        $this->decorated->setHydratorRegistry($hydratorRegistry);
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return $this->decorated->getDriverName();
    }

    /**
     * {@inheritdoc}
     */
    public function getPlatform(): Platform
    {
        return $this->decorated->getPlatform();
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugEnabled(): bool
    {
        return $this->decorated->isDebugEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function isResultMetadataSlow(): bool
    {
        return $this->decorated->isResultMetadataSlow();
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->decorated->getQueryBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getConverter(): ConverterInterface
    {
        return $this->decorated->getConverter();
    }

    /**
     * {@inheritdoc}
     */
    public function createTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = true): Transaction
    {
        return $this->decorated->createTransaction($isolationLevel, $allowPending);
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = true): Transaction
    {
        return $this->decorated->beginTransaction($isolationLevel, $allowPending);
    }

    /**
     * {@inheritdoc}
     */
    public function isTransactionPending(): bool
    {
        return $this->decorated->isTransactionPending();
    }

    /**
     * {@inheritdoc}
     */
    public function runTransaction(callable $callback, int $isolationLevel = Transaction::REPEATABLE_READ)
    {
        return $this->decorated->runTransaction($callback, $isolationLevel);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, ?string $identifier = null): string
    {
        return $this->decorated->prepareQuery($query, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery(string $identifier, $arguments = null, $options = null): ResultIterator
    {
        return $this->decorated->executePreparedQuery($identifier, $arguments, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($query, $arguments = null, $options = null): ResultIterator
    {
        return $this->decorated->execute($query, $arguments, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, $arguments = null, $options = null): int
    {
        return $this->decorated->perform($query, $arguments, $options);
    }
}
