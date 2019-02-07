<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Query\QueryBuilder;
use Goat\Query\Writer\FormatterInterface;

interface Runner
{
    /**
     * Get driver name (eg. mysql, pgsql, ...).
     */
    public function getDriverName(): string;

    /**
     * Does this driver supports SQL standard RETURNING clause
     */
    public function supportsReturning(): bool;

    /**
     * Does this driver supports deferring constraints
     */
    public function supportsDeferingConstraints(): bool;

    /**
     * Get the query builder
     */
    public function getQueryBuilder(): QueryBuilder;

    /**
     * Get SQL formatter
     */
    public function getFormatter(): FormatterInterface;

    /**
     * Creates a new transaction
     *
     * If a transaction is pending, continue the same transaction by adding a
     * new savepoint that will be transparently rollbacked in case of failure
     * in between.
     *
     * @param int $isolationLevel
     *   Default transaction isolation level, it is advised that you set it
     *   directly at this point, since some drivers don't allow isolation
     *   level changes while transaction is started
     * @param bool $allowPending = false
     *   If set to true, explicitely allow to fetch the currently pending
     *   transaction, else errors will be raised
     *
     * @throws \Goat\Runner\TransactionError
     *   If you asked a new transaction while another one is opened, or if the
     *   transaction fails starting
     *
     * @return Transaction
     */
    public function startTransaction(int $isolationLevel = Transaction::REPEATABLE_READ, bool $allowPending = false): Transaction;

    /**
     * Is there a pending transaction
     *
     * @return bool
     */
    public function isTransactionPending(): bool;

    /**
     * Run code in transaction, automatic rollback on fail.
     *
     * Exception will be rethrown.
     *
     * @param callable $callback
     *   Any callable whose first three parameters are respectively a
     *   \Goat\Query\QueryBuilder instance, a \Goat\Runner\Transaction instance
     *   and finally a \Goat\Runner\Runner instance
     *
     * @return mixed
     *   Anything that the callable returned.
     */
    public function runTransaction(callable $callback, int $isolationLevel = Transaction::REPEATABLE_READ);

    /**
     * Prepare query
     *
     * @param string|\Goat\Query\Query $query
     *   Bare SQL or Query instance.
     * @param string $identifier
     *   Query unique identifier, if null given one will be generated.
     *
     * @return string
     *   Generated unique identifier for the prepared query.
     */
    public function prepareQuery($query, ?string $identifier = null): string;

    /**
     * Prepare query
     *
     * @param string $identifier
     *   Generated unique identifier for the prepared query.
     * @param mixed[]|\Goat\Query\ArgumentBag $arguments
     *   Query arguments.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return ResultIterator
     */
    public function executePreparedQuery(string $identifier, $arguments = null, $options = null): ResultIterator;

    /**
     * Execute query with the given parameters and return the result iterator
     *
     * @param string|\Goat\Query\Query $query
     *   Arbitrary query to execute.
     * @param mixed[]|\Goat\Query\ArgumentBag $arguments
     *   Query arguments, if given $query already carries some, those will
     *   override them allowing query re-use.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return ResultIterator
     *   If query is a Query instance and ::willReturnRows() returns false, the
     *   returned result iterator will be empty and nothing will be returned, not
     *   even the affected row count.
     */
    public function execute($query, $arguments = null, $options = null): ResultIterator;

    /**
     * Execute query with the given parameters and return the affected row count
     *
     * @param string|\Goat\Query\Query $query
     *   Arbitrary query to execute
     * @param mixed[]|\Goat\Query\ArgumentBag $arguments
     *   Query arguments, if given $query already carries some, those will
     *   override them allowing query re-use.
     * @param string|mixed[] $options
     *   Query options.
     *
     * @return int
     *   Affected row count if relevant, otherwise 0.
     */
    public function perform($query, $arguments = null, $options = null): int;
}
