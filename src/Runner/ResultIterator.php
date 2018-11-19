<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Hydrator\HydratorInterface;

/**
 * When in use using the iterator, default behavior is to return associative arrays
 */
interface ResultIterator extends \IteratorAggregate, \Countable
{
    /**
     * Set converter
     */
    public function setConverter(ConverterInterface $converter): void;

    /**
     * Set hydrator
     */
    public function setHydrator(HydratorInterface $hydrator): void;

    /**
     * Set column to use as iterator key
     *
     * This will alter results from the iterator, and the fetchColumn() return.
     *
     * Please note that as a side effect, when iterating over the result, you
     * may experience duplicated keys, but because this is an iterator you will
     * get all the results, but in case you are working with fetchColumn() which
     * returns an array, some results might be lost if you encounter duplicate
     * values for keys.
     */
    public function setKeyColumn(string $name): self;

    /**
     * Get the column count
     */
    public function countColumns(): int;

    /**
     * Get the total row count
     */
    public function countRows(): int;

    /**
     * Does this column exists
     */
    public function columnExists(string $name): bool;

    /**
     * Get all column names, in select order
     *
     * @return string[]
     */
    public function getColumnNames(): array;

    /**
     * Get column type
     */
    public function getColumnType(string $name): string;

    /**
     * Get column name
     */
    public function getColumnName(int $index): string;

    /**
     * Fetch given column in the first or current row
     *
     * @param int|string $name
     *   If none given, just take the first one
     *
     * @return mixed[]
     */
    public function fetchField($name = null);

    /**
     * Fetch column
     *
     * The result of this method is altered by setKeyColumn(): if you set a key
     * column, its value will be used as keys in the return array, in case you
     * have any duplicated keys, behavior is undetermined and depends upon the
     * driver implementation: you will, in all cases, loose duplicates and have
     * an incomplete result.
     *
     * @param int|string $name = null
     *   If none given, just take the first one
     *
     * @return mixed[]
     */
    public function fetchColumn($name = null);

    /**
     * Get next element and move forward
     *
     * @return mixed
     */
    public function fetch();
}
