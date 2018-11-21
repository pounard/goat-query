<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Query\QueryBuilder;

interface Runner
{
    /**
     * Get driver name (eg. mysql, pgsql, ...).
     */
    public function getDriverName(): string;

    /**
     * Get the query builder
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder;

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
    public function prepareQuery($query, string $identifier = null): string;

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
