<?php

declare(strict_types=1);

namespace Goat\Runner;

final class TransactionSavepoint implements Transaction
{
    private $name;
    private $root;
    private $running = true;

    /**
     * Default constructor
     */
    public function __construct(string $name, Transaction $root)
    {
        $this->name = $name;
        $this->root = $root;
    }

    /**
     * {@inheritdoc}
     */
    public function level(int $isolationLevel): Transaction
    {
        \trigger_error(\sprintf("Cannot change transaction level in nested transaction with savepoint '%s'", $this->name), E_USER_NOTICE);
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted(): bool
    {
        return $this->running && $this->root->isStarted();
    }

    /**
     * {@inheritdoc}
     */
    public function start(): Transaction
    {
        if (!$this->running) {
            throw new TransactionError(\sprintf("can not restart a rollbacked transaction with savedpoint '%s'", $this->name));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function immediate($constraint = null): Transaction
    {
        if ($constraint) {
            $this->root->immediate($constraint);
        } else {
            \trigger_error(\sprintf("Cannot set all constraints to immediate in nested transaction with savepoint '%s'", $this->name), E_USER_NOTICE);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deferred($constraint = null): Transaction
    {
        if ($constraint) {
            $this->root->deferred($constraint);
        } else {
            \trigger_error(\sprintf("Cannot set all constraints to deferred in nested transaction with savepoint '%s'", $this->name), E_USER_NOTICE);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function savepoint(string $name = null): TransactionSavepoint
    {
        if ($name) {
            return $this->root->savepoint($name);
        }
        return $this->root->savepoint();
    }

    /**
     * {@inheritdoc}
     */
    public function isNested(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSavepointName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): Transaction
    {
        $this->running = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): Transaction
    {
        $this->running = false;
        $this->root->rollbackToSavepoint($this->name);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackToSavepoint(string $name): Transaction
    {
        $this->root->rollbackToSavepoint($name);

        return $this;
    }
}
