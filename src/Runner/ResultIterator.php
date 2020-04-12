<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Hydrator\HydratorInterface;
use Goat\Runner\Metadata\ResultMetadata;
use Goat\Runner\Metadata\ResultProfile;

/**
 * When in use using the iterator, default behavior is to return associative arrays
 */
interface ResultIterator extends ResultMetadata, \Traversable, \Countable
{
    /**
     * Get result profiler data.
     */
    public function getResultProfile(): ResultProfile;

    /**
     * Toggle rewindable feature.
     */
    public function setRewindable($rewindable = true): self;

    /**
     * Set converter.
     *
     * @return $this
     */
    public function setConverter(ConverterInterface $converter): self;

    /**
     * Set hydrator.
     *
     * @return $this
     */
    public function setHydrator(HydratorInterface $hydrator): self;

    /**
     * Set column to use as iterator key.
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
     * Toggle debug mode.
     */
    public function setDebug(bool $enable): void;

    /**
     * Set type map for faster hydration.
     *
     * @param string[][] $userTypes
     *   Keys are aliases from the result, values are types.
     */
    public function setMetadata(array $userTypes, ?ResultMetadata $metadata = null): self;

    /**
     * Get the total row count
     */
    public function countRows(): int;

    /**
     * Fetch given column in the first or current row.
     *
     * @param int|string $name
     *   If none given, just take the first one
     *
     * @return mixed[]
     */
    public function fetchField($name = null);

    /**
     * Fetch column.
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
     * Get next element and move forward.
     *
     * Fetch usage is discouraged if you have more than one element in the
     * result because it forces the current implementation to create an
     * extra internal iterator.
     *
     * Whenever you have more than one result, simply use foreach() over
     * the result, which will be much, much more efficient.
     *
     * @return mixed
     */
    public function fetch();
}
