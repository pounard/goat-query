<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Transaction;

class PgSQLTransaction extends AbstractTransaction
{
    /**
     * {@inheritdoc}
     */
    protected function doTransactionStart(int $isolationLevel): void
    {
        // Set immediate constraint fail per default to be ISO with
        // backends that don't support deferable constraints
        $this->runner->perform(
            \sprintf(
                "START TRANSACTION ISOLATION LEVEL %s READ WRITE",
                self::getIsolationLevelString($isolationLevel)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doChangeLevel(int $isolationLevel): void
    {
        // Set immediate constraint fail per default to be ISO with
        // backends that don't support deferable constraints
        $this->runner->perform(
            \sprintf(
                "SET TRANSACTION ISOLATION LEVEL %s",
                self::getIsolationLevelString($isolationLevel)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateSavepoint(string $name): void
    {
        $this->runner->perform(
            \sprintf(
                "SAVEPOINT %s",
                $this->escapeName($name)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doRollbackToSavepoint(string $name): void
    {
        $this->runner->perform(
            \sprintf(
                "ROLLBACK TO SAVEPOINT %s",
                $this->escapeName($name)
            )
        );
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
        $this
            ->runner
            ->perform(
                \sprintf(
                    "SET CONSTRAINTS %s DEFERRED",
                    $this->escapeNameList($constraints)
                )
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeferAll(): void
    {
        $this->runner->perform("SET CONSTRAINTS ALL DEFERRED");
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateConstraints(array $constraints): void
    {
        $this
            ->runner
            ->perform(
                \sprintf(
                    "SET CONSTRAINTS %s IMMEDIATE",
                    $this->escapeNameList($constraints)
                )
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function doImmediateAll(): void
    {
        $this->runner->perform("SET CONSTRAINTS ALL IMMEDIATE");
    }
}
