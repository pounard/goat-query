<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;
use Goat\Driver\Instrumentation\ProfilerAware;
use Goat\Driver\Instrumentation\ProfilerAwareTrait;
use Goat\Driver\Instrumentation\QueryProfiler;
use Goat\Driver\Platform\Platform;
use Goat\Driver\Query\SqlWriter;
use Goat\Query\QueryBuilder;
use Goat\Query\QueryError;
use Goat\Runner\AbstractResultIterator;
use Goat\Runner\DefaultQueryBuilder;
use Goat\Runner\ResultIterator;
use Goat\Runner\Runner;
use Goat\Runner\Transaction;
use Goat\Runner\TransactionError;
use Goat\Runner\Hydrator\HydratorRegistry;
use Goat\Runner\Metadata\ArrayResultMetadataCache;
use Goat\Runner\Metadata\ResultMetadataCache;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractRunner implements Runner, ProfilerAware
{
    use ProfilerAwareTrait;

    /** @var LoggerInterface */
    private $logger;
    /** @var Platform */
    private $platform;
    /** @var null|Transaction */
    private $currentTransaction;
    /** @var bool */
    private $debug = false;
    /** @var HydratorRegistry */
    private $hydratorRegistry;
    /** @ar QueryBuilder */
    private $queryBuilder;
    /** @var ResultMetadataCache */
    private $metadataCache;
    /** @var ConverterInterface */
    protected $converter;
    /** @var SqlWriter */
    protected $formatter;

    /**
     * Constructor
     */
    public function __construct(Platform $platform)
    {
        $this->logger = new NullLogger();
        $this->platform = $platform;
        $this->formatter = $platform->getSqlWriter();
        $this->setConverter(new DefaultConverter());
        if ($this->isResultMetadataSlow()) {
            $this->metadataCache = new ArrayResultMetadataCache();
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function getPlatform(): Platform
    {
        return $this->platform;
    }

    /**
     * Set logger.
     */
    final public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Toggle debug mode.
     */
    final public function setDebug(bool $value): void
    {
        $this->debug = $value;
    }

    /**
     * {@inheritdoc}
     */
    final public function isDebugEnabled(): bool
    {
        return $this->debug;
    }

    /**
     * {@inheritdoc}
     */
    public function isResultMetadataSlow(): bool
    {
        return false;
    }

    /**
     * Inject the result metadata cache implementation?
     */
    final public function setResultMetadataCache(ResultMetadataCache $metadataCache): void
    {
        $this->metadataCache = $metadataCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder ?? ($this->queryBuilder = new DefaultQueryBuilder($this));
    }

    /**
     * Set converter.
     *
     * @deprecated
     * @todo This needs to be sorted out, we need to be able to override
     *   converted, but properly.
     */
    public function setConverter(ConverterInterface $converter): void
    {
        $this->converter = new RunnerConverter($converter, $this->getPlatform()->getEscaper());
    }

    /**
     * {@inheritdoc}
     */
    final public function getConverter(): ConverterInterface
    {
        return $this->converter;
    }

    /**
     * {@inheritdoc}
     */
    final public function setHydratorRegistry(HydratorRegistry $hydratorRegistry): void
    {
        $this->hydratorRegistry = $hydratorRegistry;
    }

    /**
     * Get hydrator registry
     */
    final protected function getHydratorRegistry(): HydratorRegistry
    {
        if (!$this->hydratorRegistry) {
            throw new \BadMethodCallException("There is no hydrator configured");
        }

        return $this->hydratorRegistry;
    }

    /**
     * Get current transaction if any
     */
    final private function findCurrentTransaction(): ?Transaction
    {
        if ($this->currentTransaction) {
            if ($this->currentTransaction->isStarted()) {
                return $this->currentTransaction;
            } else {
                // Transparently cleanup leftovers
                unset($this->currentTransaction);
            }
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    final public function createTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = true): Transaction
    {
        $transaction = $this->findCurrentTransaction();

        if ($transaction) {
            if (!$allowPending) {
                throw new TransactionError("a transaction already been started, you cannot nest transactions");
            }
            if (!$this->platform->supportsTransactionSavepoints()) {
                throw new TransactionError("Cannot create a nested transaction, driver does not support savepoints");
            }

            $savepoint = $transaction->savepoint();

            return $savepoint;
        }

        $transaction = $this->platform->createTransaction($this, $isolationLevel);
        $this->currentTransaction = $transaction;

        return $transaction;
    }

    /**
     * {@inheritdoc}
     */
    final public function beginTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = true): Transaction
    {
        return $this->createTransaction($isolationLevel, $allowPending)->start();
    }

    /**
     * {@inheritdoc}
     */
    final public function isTransactionPending(): bool
    {
        return $this->currentTransaction && $this->currentTransaction->isStarted();
    }

    /**
     * {@inheritdoc}
     */
    public function runTransaction(callable $callback, int $isolationLevel = Transaction::REPEATABLE_READ)
    {
        $ret = null;
        $transaction = $this->beginTransaction($isolationLevel, true);

        try {
            if (!$transaction->isStarted()) {
                $transaction->start();
            }
            $ret = \call_user_func($callback, $this->getQueryBuilder(), $transaction, $this);
            $transaction->commit();

        } catch (\Throwable $e) {
            if ($transaction->isStarted()) {
                $transaction->rollback();
            }

            throw $e;
        }

        return $ret;
    }

    /**
     * Do create iterator.
     *
     * @param mixed[] $constructorArgs
     *   Driver specific parameters
     */
    abstract protected function doCreateResultIterator(...$constructorArgs) : AbstractResultIterator;

    /**
     * Create the result iterator instance.
     *
     * @param string $identifier
     *   Query identifier
     * @param string[] $options
     *   Query options
     * @param mixed[] $constructorArgs
     *   Driver specific parameters
     *
     * @return ResultIterator
     */
    final protected function createResultIterator(string $identifier, QueryProfiler $profiler, $options = null, ...$constructorArgs): ResultIterator
    {
        $profiler->stop(); // In case it was missed.

        $result = $this->doCreateResultIterator(...$constructorArgs);
        $result->setConverter($this->converter);
        $result->setQueryProfiler($profiler);

        // Normalize options, it might be a string only.
        if ($options) {
            if (\is_string($options)) {
                $options = ['class' => $options];
            } else if (!\is_array($options)) {
                throw new QueryError("options must be a valid class name or an array of options");
            }
        }

        if (isset($options['hydrator'])) {
            if (isset($options['class'])) {
                $this->logger->warning("'hydrator' option overrides the 'class' option");
            }
            if (!\is_callable($options['hydrator'])) {
                throw new QueryError(\sprintf("'hydrator' option must be a callable, found '%s'", \gettype($options['hydrator'])));
            }
            $result->setHydrator($options['hydrator']);
        } else if (isset($options['class'])) {
            // Class can be either an alias or a valid class name, the hydrator
            // will proceed with all runtime checks to ensure that.
            $result->setHydrator($this->getHydratorRegistry()->getHydrator($options['class']));
        }

        $userTypes = [];
        if (!empty($options['types'])) {
            if (!\is_array($options['types'])) {
                throw new QueryError(\sprintf("'types' option must be a string array, keys are result column aliases, values are types"));
            }
            $userTypes = $options['types'];
        }

        if ($this->debug || (isset($options['debug']) && $options['debug'])) {
            $result->setDebug(true);
        }

        // Handle metadata cache.
        if ($this->metadataCache && $this->isResultMetadataSlow()) {
            // Force result iterator to fetch all column information, it does
            // really mater to impose this since hydration process will do
            // it in the end. User types feature was originally created as a
            // way to suppress this performance penalty, but it is not anymore
            // and serves the only purpose of allow the user to tweak object
            // hydration.
            if ($metadata = $this->metadataCache->fetch($identifier)) {
                $result->setMetadata($userTypes, $metadata);
            } else {
                $result->setMetadata($userTypes);
                $this->metadataCache->store($identifier, $result->getColumnNames(), $result->getColumnTypes());
            }
        } else {
            $result->setMetadata($userTypes);
        }

        return $result;
    }
}
