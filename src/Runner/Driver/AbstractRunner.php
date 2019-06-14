<?php

declare(strict_types=1);

namespace Goat\Runner\Driver;

use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;
use Goat\Hydrator\HydratorInterface;
use Goat\Hydrator\HydratorMap;
use Goat\Query\ArgumentBag;
use Goat\Query\ArgumentList;
use Goat\Query\QueryBuilder;
use Goat\Query\QueryError;
use Goat\Query\Writer\EscaperInterface;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\ResultIterator;
use Goat\Runner\Runner;
use Goat\Runner\Transaction;
use Goat\Runner\TransactionError;

abstract class AbstractRunner implements Runner, EscaperInterface
{
    private $currentTransaction;
    private $debug = false;
    private $hydratorMap;
    private $queryBuilder;
    protected $converter;
    protected $dsn;
    protected $escaper;
    protected $formatter;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->formatter = $this->createFormatter();
        $this->formatter->setEscaper($this);
        $this->setConverter(new DefaultConverter());
    }

    /**
     * {@inheritdoc}
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
     *
     * Sensible default since most major RDBMS support savepoints
     */
    public function supportsTransactionSavepoints(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * Only MySQL does not support this, so this is a sensible default.
     */
    public function supportsReturning(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder ?? ($this->queryBuilder = new DefaultQueryBuilder($this));
    }

    /**
     * Set converter
     */
    public function setConverter(ConverterInterface $converter): void
    {
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    final public function getFormatter(): FormatterInterface
    {
        return $this->formatter;
    }

    /**
     * Get escaper
     *
     * @internal For profiling and debugging purpose only
     */
    final protected function getEscaper(): EscaperInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function escapeIdentifierList($strings): string
    {
        if (!$strings) {
            throw new QueryError("cannot not format an empty identifier list");
        }
        if (\is_array($strings)) {
            return \implode(', ', \array_map([$this, 'escapeIdentifier'], $strings));
        }
        return $this->escapeIdentifier($strings);
    }

    /**
     * {@inheritdoc}
     */
    final public function setHydratorMap(HydratorMap $hydratorMap): void
    {
        $this->hydratorMap = $hydratorMap;
    }

    /**
     * {@inheritdoc}
     */
    final public function getHydratorMap(): HydratorMap
    {
        if (!$this->hydratorMap) {
            throw new \BadMethodCallException("There is no hydrator configured");
        }

        return $this->hydratorMap;
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
            if (!$this->supportsTransactionSavepoints()) {
                throw new TransactionError("Cannot create a nested transaction, driver does not support savepoints");
            }
            return $transaction->savepoint();
        }

        $transaction = $this->doStartTransaction($isolationLevel);
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
     * Create a new transaction object
     */
    abstract protected function doStartTransaction(int $isolationLevel = Transaction::REPEATABLE_READ): Transaction;

    /**
     * Create SQL formatter
     */
    abstract protected function createFormatter(): FormatterInterface;

    /**
     * Do create iterator
     *
     * @param mixed[] $constructorArgs
     *   Driver specific parameters
     */
    abstract protected function doCreateResultIterator(...$constructorArgs) : ResultIterator;

    /**
     * Prepare arguments.
     *
     * ArgumentList comes from the SQL formatter, and gives us information
     * about the number of argument and their types if found. Based upon this
     * information, it will:
     *
     *   - either just convert given arguments if it's a bare array,
     *   - merge type information then convert if it's an ArgumentBag.
     *
     * In all cases, it will reconcile the awaited parameter count and raise
     * errors if the number doesn't match.
     */
    final protected function prepareArguments(ArgumentList $argumentList, $arguments): array
    {
        $ret = [];

        // J'aime pas ça, ce n'est pas très élégant.
        if ($arguments instanceof ArgumentBag) {
            $input = $arguments->getAll();
            $types = $argumentList->getTypeMap($arguments);
        } else if (\is_array($arguments)) {
            // Convert names to positions.
            $input = [];
            foreach ($arguments as $index => $value) {
                if (\is_int($index)) {
                    $input[$index] = $value;
                } else {
                    $input[$argumentList->getNameIndex($index)] = $value;
                }
            }
            $types = $argumentList->getTypeMap();
        } else {
            throw new QueryError(\sprintf(
                "\$arguments must be a %s instance or an array, %s given",
                ArgumentBag::class, \gettype($arguments)
            ));
        }

        $count = $argumentList->count();

        if (\count($input) !== $count) {
            throw new QueryError(\sprintf("Invalid parameter number bound"));
        }

        for ($i = 0; $i < $count; ++$i) {
            $ret[$i] = $this->converter->toSQL($types[$i] ?? ConverterInterface::TYPE_UNKNOWN, $input[$i]);
        }

        return $ret;
    }

    /**
     * Create the result iterator instance
     *
     * @param string[] $options
     *   Query options
     * @param mixed[] $constructorArgs
     *   Driver specific parameters
     *
     * @return ResultIterator
     */
    final protected function createResultIterator($options = null, ...$constructorArgs): ResultIterator
    {
        $result = $this->doCreateResultIterator(...$constructorArgs);
        $result->setConverter($this->converter);

        if ($options) {
            if (\is_string($options)) {
                $options = ['class' => $options];
            } else if (!\is_array($options)) {
                throw new QueryError("options must be a valid class name or an array of options");
            }
        }

        if (isset($options['hydrator'])) {
            if (isset($options['class']) && $this->isDebugEnabled()) {
                \trigger_error("'hydrator' option overrides the 'class' option", E_USER_WARNING);
            }
            if (!$options['hydrator'] instanceof HydratorInterface) {
                throw new QueryError(\sprintf(
                    "'hydrator' option must be an instance of '%s' or a callable, found '%s'",
                    HydratorInterface::class, \gettype($options['hydrator'])
                ));
            }
            $result->setHydrator($options['hydrator']);
        } else if (isset($options['class'])) {
            // Class can be either an alias or a valid class name, the hydrator
            // will proceed with all runtime checks to ensure that.
            $result->setHydrator($this->getHydratorMap()->get($options['class']));
        }

        if (!empty($options['types'])) {
            if (!\is_array($options['types'])) {
                throw new QueryError(\sprintf("'types' option must be a string array, keys are result column aliases, values are types"));
            }
            $result->setTypeMap($options['types']);
        }

        if ($this->debug || (isset($options['debug']) && $options['debug'])) {
            $result->setDebug(true);
        }

        return $result;
    }
}
