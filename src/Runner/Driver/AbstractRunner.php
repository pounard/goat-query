<?php

declare(strict_types=1);

namespace Goat\Runner\Driver;

use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;
use Goat\Hydrator\HydratorMap;
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
        $this->formatter->setConverter($converter);
    }

    /**
     * Get escaper
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
        if (!\is_array($strings)) {
            $strings = [$strings];
        }

        return \implode(', ', \array_map([$this, 'escapeIdentifier'], $strings));
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
     * {@inheritdoc}
     */
    final public function startTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = false): Transaction
    {
        // Fetch transaction from the WeakRef if possible
        if ($this->currentTransaction) {
            // We need to proceed to additional checks to ensure the pending
            // transaction still exists and si started, using WeakRef the
            // object could already have been garbage collected
            if ($this->currentTransaction->isStarted()) {
                if (!$allowPending) {
                    throw new TransactionError("a transaction already been started, you cannot nest transactions");
                }

                return $this->currentTransaction;

            } else {
                unset($this->currentTransaction);
            }
        }

        // Acquire a weak reference if possible, this will allow the transaction
        // to fail upon __destruct() when the user leaves the transaction scope
        // without closing it properly. Without the ext-weakref extension, the
        // transaction will fail during PHP shutdown instead, errors will be
        // less understandable for the developper, and code will fail much later
        // and possibly run lots of things it should not. Since it's during a
        // pending transaction it will not cause data consistency bugs, it will
        // just make it harder to debug.
        $transaction = $this->doStartTransaction($isolationLevel);
        $this->currentTransaction = $transaction;

        return $transaction;
    }

    /**
     * {@inheritdoc}
     */
    final public function isTransactionPending(): bool
    {
        return $this->currentTransaction && $this->currentTransaction->isStarted();
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

        if (isset($options['class'])) {
            // Class can be either an alias or a valid class name, the hydrator
            // will proceed with all runtime checks to ensure that.
            $result->setHydrator($this->getHydratorMap()->get($options['class']));
        }

        return $result;
    }
}
