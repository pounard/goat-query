<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Transaction;

use Goat\Runner\ServerError;
use Goat\Runner\TransactionError;
use Goat\Runner\TransactionFailedError;

class MySQLTransaction extends AbstractTransaction
{
    /**
     * {@inheritdoc}
     */
    protected function doTransactionStart(int $isolationLevel): void
    {
        try {
            // Transaction level cannot be changed while in the transaction,
            // so it must set before starting the transaction
            $this->runner->perform(
                \sprintf(
                    "SET TRANSACTION ISOLATION LEVEL %s",
                    self::getIsolationLevelString($isolationLevel)
                )
            );
        } catch (ServerError $e) {
            // Gracefully continue without changing the transaction isolation
            // level, MySQL don't support it, but we cannot penalize our users;
            // beware that users might use a transaction with a lower level
            // than they asked for, and data consistency is not ensured anymore
            // that's the downside of using MySQL.
            if (1568 == $e->getCode()) {
                /* if ($this->runner->isDebugEnabled()) {
                    @\trigger_error("transaction is nested into another, MySQL can't change the isolation level", E_USER_NOTICE);
                } */
            }

            throw $e;
        }

        $this->runner->perform("BEGIN");
    }

    /**
     * {@inheritdoc}
     */
    protected function doChangeLevel(int $isolationLevel): void
    {
        /* if ($this->runner->isDebugEnabled()) {
            @\trigger_error("MySQL does not support transaction level change during transaction", E_USER_NOTICE);
        } */
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateSavepoint(string $name): void
    {
        $this->runner->perform(\sprintf(
            "SAVEPOINT %s",
            $this->escapeName($name)
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function doRollbackToSavepoint(string $name): void
    {
        $this->runner->perform(\sprintf(
            "ROLLBACK TO SAVEPOINT %s",
            $this->escapeName($name)
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function doRollback(): void
    {
        $this->runner->perform("ROLLBACK");
    }

    /**
     * {@inheritdoc}
     */
    protected function doCommit(): void
    {
        $this->runner->perform("COMMIT");
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferConstraints(array $constraints): void
    {
        /* if ($this->runner->isDebugEnabled()) {
            @\trigger_error("MySQL does not support deferred constraints", E_USER_NOTICE);
        } */
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferAll(): void
    {
        /* if ($this->runner->isDebugEnabled()) {
            @\trigger_error("MySQL does not support deferred constraints", E_USER_NOTICE);
        } */
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateConstraints(array $constraints): void
    {
        // Do nothing, as MySQL always check constraints immediatly
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateAll(): void
    {
        // Do nothing, as MySQL always check constraints immediatly
    }
}
