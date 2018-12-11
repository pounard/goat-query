<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Query\Writer\EscaperInterface;

/**
 * Base implementation of the Transaction interface that prevents logic errors.
 */
abstract class AbstractTransaction implements Transaction
{
    /**
     * Default savepoint name prefix
     */
    const SAVEPOINT_PREFIX = 'gsp_';

    /**
     * Get transaction level string
     */
    public static function getIsolationLevelString(int $isolationLevel): string
    {
        switch ($isolationLevel) {

            case Transaction::READ_UNCOMMITED:
                return 'READ UNCOMMITTED';

            case Transaction::READ_COMMITED:
                return 'READ COMMITTED';

            case Transaction::REPEATABLE_READ:
                return 'REPEATABLE READ';

            case Transaction::SERIALIZABLE:
                return 'SERIALIZABLE';

            default:
                throw new TransactionError(\sprintf("%s: unknown transaction level", $isolationLevel));
        }
    }

    private $isolationLevel = self::REPEATABLE_READ;
    private $savepoint = 0;
    private $savepoints = [];
    private $started = false;
    protected $runner;

    /**
     * Default constructor
     */
    final public function __construct(Runner $runner, int $isolationLevel = self::REPEATABLE_READ)
    {
        $this->runner = $runner;
        $this->level($isolationLevel);
    }

    /**
     * Default destructor
     *
     * Started transactions should not be left opened, this will force a
     * transaction rollback and throw an exception
     */
    final public function __destruct()
    {
        if ($this->started) {
            $this->rollback();

            throw new TransactionError(\sprintf("transactions should never be left opened"));
        }
    }

    /**
     * Escape name list
     */
    protected function escapeNameList(array $names): string
    {
        if ($this->runner instanceof EscaperInterface) {
            return $this->runner->escapeIdentifierList($names);
        }

        return \implode(', ', $names);
    }

    /**
     * Escape name
     */
    protected function escapeName(string $name): string
    {
        if ($this->runner instanceof EscaperInterface) {
            return $this->runner->escapeIdentifier($name);
        }

        return $name;
    }

    /**
     * Starts the transaction
     */
    abstract protected function doTransactionStart(int $isolationLevel): void;

    /**
     * Change transaction level
     */
    abstract protected function doChangeLevel(int $isolationLevel): void;

    /**
     * Create savepoint
     */
    abstract protected function doCreateSavepoint(string $name): void;

    /**
     * Rollback to savepoint
     */
    abstract protected function doRollbackToSavepoint(string $name): void;

    /**
     * Rollback
     */
    abstract protected function doRollback(): void;

    /**
     * Commit
     */
    abstract protected function doCommit(): void;

    /**
     * Defer given constraints
     *
     * @param string[] $constraints
     *   Constraint name list
     */
    abstract protected function doDeferConstraints(array $constraints): void;

    /**
     * Defer all constraints
     */
    abstract protected function doDeferAll(): void;

    /**
     * Set given constraints as immediate
     *
     * @param string[] $constraints
     *   Constraint name list
     */
    abstract protected function doImmediateConstraints(array $constraints): void;

    /**
     * Set all constraints as immediate
     */
    abstract protected function doImmediateAll(): void;

    /**
     * {@inheritdoc}
     */
    public function level(int $isolationLevel): Transaction
    {
        if ($isolationLevel === $this->isolationLevel) {
            return $this; // Nothing to be done
        }

        if ($this->started) {
            $this->doChangeLevel($isolationLevel);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): Transaction
    {
        $this->doTransactionStart($this->isolationLevel);

        $this->started = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function immediate($constraint = null): Transaction
    {
        if ($constraint) {
            if (!\is_array($constraint)) {
                $constraint = [$constraint];
            }
            $this->doImmediateConstraints($constraint);
        } else {
            $this->doImmediateAll();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function deferred($constraint = null): Transaction
    {
        if ($constraint) {
            if (!\is_array($constraint)) {
                $constraint = [$constraint];
            }
            $this->doDeferConstraints($constraint);
        } else {
            $this->doDeferAll();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function savepoint(string $name = null): string
    {
        if (!$this->started) {
            throw new TransactionError(\sprintf("can not commit a non-running transaction"));
        }

        if (!$name) {
            $name = self::SAVEPOINT_PREFIX.(++$this->savepoint);
        }

        if (isset($this->savepoints[$name])) {
            throw new TransactionError(\sprintf("%s: savepoint already exists", $name));
        }

        $this->doCreateSavepoint($name);

        $this->savepoints[$name] = true;

        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): Transaction
    {
        if (!$this->started) {
            throw new TransactionError(\sprintf("can not commit a non-running transaction"));
        }

        $this->doCommit();

        // This code will be reached only if the commit failed, the transaction
        // not beeing stopped at the application level allows you to call
        // rollbacks later.
        $this->started = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): Transaction
    {
        if (!$this->started) {
            throw new TransactionError(\sprintf("can not rollback a non-running transaction"));
        }

        // Even if the rollback fails and throw exceptions, this transaction
        // is dead in the woods, just mark it as stopped.
        $this->started = false;

        $this->doRollback();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackToSavepoint(string $name): Transaction
    {
        if (!$this->started) {
            throw new TransactionError(\sprintf("can not rollback to savepoint in a non-running transaction"));
        }
        if (!isset($this->savepoints[$name])) {
            throw new TransactionError(\sprintf("%s: savepoint does not exists or is not handled by this object", $name));
        }

        $this->doRollbackToSavepoint($name);

        return $this;
    }
}
