<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Transaction;

use Goat\Runner\ServerError;
use Goat\Runner\TransactionError;
use Goat\Runner\TransactionFailedError;

class PgSQLTransaction extends AbstractTransaction
{
    /**
     * {@inheritdoc}
     */
    protected function doTransactionStart(int $isolationLevel): void
    {
        try {
            // Set immediate constraint fail per default to be ISO with
            // backends that don't support deferable constraints
            $this->runner->perform(
                \sprintf(
                    "START TRANSACTION ISOLATION LEVEL %s READ WRITE",
                    self::getIsolationLevelString($isolationLevel)
                )
            );
        } catch (ServerError $e) {
            throw new TransactionError("transaction start failed", null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doChangeLevel(int $isolationLevel): void
    {
        try {
            // Set immediate constraint fail per default to be ISO with
            // backends that don't support deferable constraints
            $this->runner->perform(
                \sprintf(
                    "SET TRANSACTION ISOLATION LEVEL %s",
                    self::getIsolationLevelString($isolationLevel)
                )
            );
        } catch (ServerError $e) {
            throw new TransactionError("transaction set failed", null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateSavepoint(string $name): void
    {
        try {
            $this->runner->perform(
                \sprintf(
                    "SAVEPOINT %s",
                    $this->escapeName($name)
                )
            );
        } catch (ServerError $e) {
            throw new TransactionError(\sprintf("%s: create savepoint failed", $name), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doRollbackToSavepoint(string $name): void
    {
        try {
            $this->runner->perform(
                \sprintf(
                    "ROLLBACK TO SAVEPOINT %s",
                    $this->escapeName($name)
                )
            );
        } catch (ServerError $e) {
            throw new TransactionError(\sprintf("%s: rollback to savepoint failed", $name), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doRollback(): void
    {
        try {
            $this->runner->perform("ROLLBACK");
        } catch (ServerError $e) {
            throw new TransactionError("", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doCommit(): void
    {
        try {
            $this->runner->perform("COMMIT");
        } catch (ServerError $e) {
            throw new TransactionFailedError("", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferConstraints(array $constraints): void
    {
        try {
            $this
                ->runner
                ->perform(
                    \sprintf(
                        "SET CONSTRAINTS %s DEFERRED",
                        $this->escapeNameList($constraints)
                    )
                )
            ;
        } catch (ServerError $e) {
            throw new TransactionFailedError("", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferAll(): void
    {
        try {
            $this->runner->perform("SET CONSTRAINTS ALL DEFERRED");
        } catch (ServerError $e) {
            throw new TransactionFailedError("", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateConstraints(array $constraints): void
    {
        try {
            $this
                ->runner
                ->perform(
                    \sprintf(
                        "SET CONSTRAINTS %s IMMEDIATE",
                        $this->escapeNameList($constraints)
                    )
                )
            ;
        } catch (ServerError $e) {
            throw new TransactionFailedError("", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateAll(): void
    {
        try {
            $this->runner->perform("SET CONSTRAINTS ALL IMMEDIATE");
        } catch (ServerError $e) {
            throw new TransactionFailedError("", 0, $e);
        }
    }
}
