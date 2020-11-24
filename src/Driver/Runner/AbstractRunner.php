<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;
use Goat\Driver\Error\TransactionError;
use Goat\Driver\Instrumentation\ProfilerAware;
use Goat\Driver\Instrumentation\ProfilerAwareTrait;
use Goat\Driver\Platform\Platform;
use Goat\Driver\Query\FormattedQuery;
use Goat\Driver\Query\SqlWriter;
use Goat\Query\Query;
use Goat\Query\QueryBuilder;
use Goat\Query\QueryError;
use Goat\Runner\AbstractResultIterator;
use Goat\Runner\DatabaseError;
use Goat\Runner\DefaultQueryBuilder;
use Goat\Runner\EmptyResultIterator;
use Goat\Runner\ResultIterator;
use Goat\Runner\Runner;
use Goat\Runner\ServerError;
use Goat\Runner\Transaction;
use Goat\Runner\Hydrator\DefaultHydratorRegistry;
use Goat\Runner\Hydrator\HydratorRegistry;
use Goat\Runner\Metadata\ArrayResultMetadataCache;
use Goat\Runner\Metadata\ResultMetadataCache;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractRunner implements Runner, ProfilerAware
{
    use ProfilerAwareTrait;

    private LoggerInterface $logger;
    private Platform $platform;
    private ?Transaction $currentTransaction = null;
    private bool $debug = false;
    private ?HydratorRegistry $hydratorRegistry = null;
    private ?QueryBuilder $queryBuilder = null;
    private ?ResultMetadataCache $metadataCache = null;
    protected ConverterInterface $converter;
    protected SqlWriter $formatter;

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

        if ($value) {
            $this->initializeProfiler();
        }
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
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder ?? ($this->queryBuilder = new DefaultQueryBuilder($this));
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
            $this->hydratorRegistry = DefaultHydratorRegistry::createDefaultInstance();
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
    private function configureResultIterator(string $identifier, ResultIterator $result, array $options): ResultIterator
    {
        $result->setConverter($this->converter);

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

    /**
     * Normalize options.
     */
    private function normalizeOptions($options = null): array
    {
        // Normalize options, it might be a string only.
        if ($options) {
            if (\is_string($options)) {
                $options = ['class' => $options];
            } else if (!\is_array($options)) {
                throw new QueryError("options must be a valid class name or an array of options");
            }
        } else {
            $options = [];
        }

        return $options;
    }

    /**
     * execute() implementation.
     */
    protected abstract function doExecute(string $sql, array $args, array $options): AbstractResultIterator;

    /**
     * perform() implementation.
     */
    protected abstract function doPerform(string $sql, array $args, array $options): int;

    /**
     * prepareQuery() implementation.
     */
    protected abstract function doPrepareQuery(string $identifier, FormattedQuery $prepared, array $options): void;

    /**
     * executePreparedQuery() implementation.
     */
    protected abstract function doExecutePreparedQuery(string $identifier, array $args, array $options): AbstractResultIterator;

    /**
     * {@inheritdoc}
     */
    public function execute($query, $arguments = null, $options = null): ResultIterator
    {
        if ($query instanceof Query) {
            if (!$query->willReturnRows()) {
                $affectedRowCount = $this->perform($query, $arguments, $options);

                return new EmptyResultIterator($affectedRowCount);
            }
        }

        $options = $this->normalizeOptions($options);
        $args = null;
        $rawSQL = '';
        $profiler = $this->startProfilerQuery();

        try {
            $profiler->begin('prepare');
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->toString();
            $args = $prepared->prepareArgumentsWith($this->converter, $arguments);
            $profiler->end('prepare');

            $profiler->begin('execute');
            $result = $this->doExecute($rawSQL, $args ?? [], $options);
            $profiler->end('execute');

            $result->setQueryProfiler($profiler);

            return $this->configureResultIterator($prepared->getIdentifier(), $result, $options);

        } catch (DatabaseError $e) {
            if ($this->isTransactionPending()) {
                throw TransactionError::fromException($e);
            }
            throw $e;
        } catch (\Throwable $e) {
            throw new ServerError($rawSQL, null, $e);
        } finally {
            $profiler->stop();
            if ($this->isDebugEnabled()) {
                $profiler->setRawSql($rawSQL, $args);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, $arguments = null, $options = null) : int
    {
        $options = $this->normalizeOptions($options);
        $args = null;
        $rawSQL = '';
        $profiler = $this->startProfilerQuery();

        try {
            $profiler->begin('prepare');
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->toString();
            $args = $prepared->prepareArgumentsWith($this->converter, $arguments);
            $profiler->end('prepare');

            $profiler->begin('execute');
            $rowCount = $this->doPerform($rawSQL, $args ?? [], $options);
            $profiler->end('execute');

            return $rowCount;

        } catch (DatabaseError $e) {
            if ($this->isTransactionPending()) {
                throw TransactionError::fromException($e);
            }
            throw $e;
        } catch (\Throwable $e) {
            throw new ServerError($rawSQL, null, $e);
        } finally {
            $profiler->stop();
            if ($this->isDebugEnabled()) {
                $profiler->setRawSql($rawSQL, $args);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, string $identifier = null): string
    {
        $rawSQL = '';
        $profiler = $this->startProfilerQuery();

        try {
            $profiler->begin('prepare');
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->toString();
            $profiler->end('prepare');

            if (null === $identifier) {
                $identifier = \md5($rawSQL); // @todo fast and collision low probability enought?
            }

            $profiler->begin('execute');
            $this->doPrepareQuery($identifier, $prepared, [] /* no options here? */);
            $profiler->end('execute');

            return $identifier;

        } catch (DatabaseError $e) {
            if ($this->isTransactionPending()) {
                throw TransactionError::fromException($e);
            }
            throw $e;
        } catch (\Throwable $e) {
            throw new ServerError($rawSQL, null, $e);
        } finally {
            $profiler->stop();
            if ($this->isDebugEnabled()) {
                $profiler->setRawSql('<prepare statement> ' . $identifier, [$rawSQL]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery(string $identifier, $arguments = null, $options = null): ResultIterator
    {
        $options = $this->normalizeOptions($options);
        $args = $arguments ?? [];
        $profiler = $this->startProfilerQuery();

        try {
            $profiler->begin('execute');
            $result = $this->doExecutePreparedQuery($identifier, $args, $options);
            $profiler->end('execute');

            $result->setQueryProfiler($profiler);

            return $this->configureResultIterator($identifier, $result, $options);

        } catch (DatabaseError $e) {
            if ($this->isTransactionPending()) {
                throw TransactionError::fromException($e);
            }
            throw $e;
        } catch (\Throwable $e) {
            throw new ServerError($identifier, null, $e);
        } finally {
            $profiler->stop();
            if ($this->isDebugEnabled()) {
                $profiler->setRawSql("<execute prepared statement> " . $identifier, $args);
            }
        }
    }
}
