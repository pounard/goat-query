<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Runner\ResultIterator;
use Goat\Runner\Runner;

final class PreparedQuery implements Query
{
    private $arguments;
    private $identifier;
    private $initializer;
    private $options = [];
    private $runner;
    private $sqlIdentifier;
    private $willReturnRows = false;

    /**
     * Default constructor
     */
    public function __construct(Runner $runner, callable $callback, ?string $identifier = null)
    {
        $this->identifier = $identifier;
        $this->initializer = $callback;
        $this->runner = $runner;
    }

    /**
     * Lazy initialize the object and return the identifier
     */
    private function initialize(): string
    {
        if ($this->initializer) {
            // Free memory even in case of error during callback execution,
            // this also ensure that in case of bugguy initializer, it wont
            // be called more than once.
            $initializer = $this->initializer;
            $this->initializer = null;

            $query = \call_user_func($initializer);

            if (!$query) {
                throw new QueryError(\sprintf("Initializer callback did not return a %s instance", Query::class));
            }
            if ($query instanceof PreparedQuery) {
                throw new QueryError(\sprintf("%s cannot nest %s instances", __CLASS__, __CLASS__));
            }

            $this->arguments = $query->getArguments();
            $this->options = $query->getOptions();
            $this->willReturnRows = $query->willReturnRows();

            // Prepare query at construct time, because we are going to execute
            // it directly, this object was lazily instanciated the moment the
            // user called the QueryBuilder::prepare() method.
            $this->sqlIdentifier = $this->runner->prepareQuery($query, $this->identifier);
        }

        if (!$this->sqlIdentifier) {
            throw new QueryError("Prepared query is not fully initialized, initializer callback has propably raised exceptions.");
        }

        return $this->sqlIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public function setRunner(Runner $runner): void
    {
        if ($this->runner->isDebugEnabled()) {
            throw new \BadMethodCallException("Object is immutable");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier ?? $this->sqlIdentifier ?? (
            $this->sqlIdentifier = $this->initialize()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setIdentifier(string $identifier): Query
    {
        if ($this->runner->isDebugEnabled()) {
            throw new \BadMethodCallException("Object is immutable");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRelation(): ?ExpressionRelation
    {
        if ($this->runner->isDebugEnabled()) {
            throw new \BadMethodCallException("Relation cannot be fetched on an already prepared object");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setOption(string $name, $value): Query
    {
        throw new \BadMethodCallException("Object is immutable");
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options): Query
    {
        if ($this->runner->isDebugEnabled()) {
            throw new \BadMethodCallException("Object is immutable");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions($overrides = null): array
    {
        // We need the query to have been processed in order to have
        // original options to merge with.
        if (!$this->sqlIdentifier) {
            $this->initialize();
        }

        if ($overrides) {
            if (!\is_array($overrides)) {
                $overrides = ['class' => $overrides];
            }
            $options = \array_merge($this->options, $overrides);
        } else {
            $options = $this->options;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($arguments = null, $options = null): ResultIterator
    {
        return $this->runner->executePreparedQuery(
            $this->initialize(),
            $arguments,
            $this->getOptions($options)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function perform($arguments = null, $options = null): int
    {
        return $this->execute($arguments, $options)->countRows();
    }

    /**
     * {@inheritdoc}
     */
    public function willReturnRows(): bool
    {
        if (!$this->sqlIdentifier) {
            $this->initialize();
        }

        return $this->willReturnRows;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        if (!$this->sqlIdentifier) {
            $this->initialize();
        }

        return $this->arguments;
    }
}
