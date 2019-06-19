<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Runner\ResultIterator;
use Goat\Runner\Runner;

interface Query extends Statement
{
    const JOIN_INNER = 4;
    const JOIN_LEFT = 2;
    const JOIN_LEFT_OUTER = 3;
    const JOIN_RIGHT = 5;
    const JOIN_RIGHT_OUTER = 6;
    const JOIN_NATURAL = 1;
    const NULL_FIRST = 2;
    const NULL_IGNORE = 0;
    const NULL_LAST = 1;
    const ORDER_ASC = 1;
    const ORDER_DESC = 2;

    /**
     * Set runner
     *
     * @internal
     */
    public function setRunner(Runner $runner): void;

    /**
     * Get query identifier
     *
     * @see Query::setIdentifier()
     */
    public function getIdentifier(): ?string;

    /**
     * Set query unique identifier
     *
     * This identifier will serve two different purpose:
     *
     *   - if prepared, it will be the server side identifier of the prepared
     *     query, which allows you to call it more than once,
     *
     *   - if your backend is slow to fetch metadata, and marked as such, it
     *     will also serve the purpose of storing SQL query metadata, such as
     *     return types and column names and index mapping.
     *
     * In real life, each time you ask for a column type, no matter the database
     * driver in use under, fetching metadata will implicitely do SQL queries to
     * the server to ask for each column type.
     *
     * ext-pgsql driver has the courtesy of storing those in cache, which makes
     * it very efficient, skipping most of the round trips, but PDO will do as
     * much SQL query as the number of SQL query you'll run multiplied by the
     * number of returned columns.
     *
     * If your built queries are not dynamic, please always set an identifier.
     */
    public function setIdentifier(string $identifier): Query;

    /**
     * Get SQL from relation
     */
    public function getRelation(): ?ExpressionRelation;

    /**
     * Set a single query options
     *
     * null value means reset to default.
     */
    public function setOption(string $name, $value): Query;

    /**
     * Set all options from
     *
     * null value means reset to default.
     */
    public function setOptions(array $options): Query;

    /**
     * Get normalized options
     *
     * @param null|string|array
     *
     * @return array
     */
    public function getOptions($overrides = null): array;

    /**
     * Execute query with the given parameters and return the result iterator
     *
     * @param mixed[] $arguments
     *   Parameters or overrides for the query. When a Query instance is given
     *   as query and it carries parameters, this array will serve as a set of
     *   overrides for existing parameters.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return ResultIterator
     */
    public function execute($arguments = null, $options = null): ResultIterator;

    /**
     * Execute query with the given parameters and return the affected row count
     *
     * @param mixed[] $arguments
     *   Parameters or overrides for the query. When a Query instance is given
     *   as query and it carries parameters, this array will serve as a set of
     *   overrides for existing parameters.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return int
     */
    public function perform($arguments = null, $options = null): int;

    /**
     * Should this query return something
     *
     * For INSERT, MERGE, UPDATE or DELETE queries without a RETURNING clause
     * this should return false, same goes for PostgresSQL PERFORM.
     *
     * Note that SELECT queries might also be run with a PERFORM returning
     * nothing, for example in some cases with FOR UPDATE.
     *
     * This may trigger some optimizations, for example with PDO this will
     * force the RETURN_AFFECTED behavior.
     *
     * @return bool
     */
    public function willReturnRows(): bool;
}
