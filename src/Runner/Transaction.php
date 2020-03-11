<?php

declare(strict_types=1);

namespace Goat\Runner;

interface Transaction
{
    const READ_UNCOMMITED = 1;
    const READ_COMMITED = 2;
    const REPEATABLE_READ = 3;
    const SERIALIZABLE = 4;

    /**
     * Change transaction level
     *
     * @param int $isolationLevel
     *   One of the Transaction::* constants
     *
     * @return $this
     */
    public function level(int $isolationLevel): Transaction;

    /**
     * Is transaction started
     *
     * @return bool
     */
    public function isStarted(): bool;

    /**
     * Start the transaction
     *
     * @return $this
     */
    public function start(): Transaction;

    /**
     * Set as immediate all or a set of constraints
     *
     * @param string|string[]
     *   If set to null, all constraints are set immediate
     *   If a string or a string array, only the given constraint
     *   names are set as immediate
     *
     * @return $this
     */
    public function immediate($constraint = null): Transaction;

    /**
     * Defer all or a set of constraints
     *
     * @param string|string[]
     *   If set to null, all constraints are set immediate
     *   If a string or a string array, only the given constraint
     *   names are set as immediate
     *
     * @return $this
     */
    public function deferred($constraint = null): Transaction;

    /**
     * Creates a savepoint and return its name
     *
     * @param string $name
     *   Optional user given savepoint name, if none provided a name will be
     *   automatically computed using a serial
     *
     * @throws TransactionError
     *   If savepoint name already exists
     *
     * @return TransactionSavepoint
     *   The nested transaction
     */
    public function savepoint(string $name = null): TransactionSavepoint;

    /**
     * Is transaction nested (ie. is a savepoint)
     */
    public function isNested(): bool;

    /**
     * Get transaction generated name.
     */
    public function getName(): string;

    /**
     * Get savepoint name, if transaction is a savepoint, null otherwise
     */
    public function getSavepointName(): ?string;

    /**
     * Explicit transaction commit
     *
     * @return $this
     */
    public function commit(): Transaction;

    /**
     * Explicit transaction rollback
     *
     * @return $this
     */
    public function rollback(): Transaction;

    /**
     * Rollback to savepoint
     *
     * @param string $name
     *   Savepoint name
     *
     * @return $this
     */
    public function rollbackToSavepoint(string $name): Transaction;
}
