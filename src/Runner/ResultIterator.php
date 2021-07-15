<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterContext;
use Goat\Runner\Metadata\ResultMetadata;
use MakinaCorpus\Profiling\Profiler;

/**
 * When in use using the iterator, default behavior is to return associative arrays
 */
interface ResultIterator extends ResultMetadata, \Traversable, \Countable
{
    /**
     * Get result profiler data.
     */
    public function getQueryProfiler(): Profiler;

    /**
     * Toggle rewindable feature.
     */
    public function setRewindable($rewindable = true): self;

    /**
     * Set converter.
     *
     * @return $this
     */
    public function setConverterContext(ConverterContext $context): self;

    /**
     * Set hydrator.
     *
     * @param callable $hydrator
     *   Two variants of the callable signature exists:
     *    - fn (Row $row, ConverterContext $context) which is recommended.
     *    - fn (array $row) which is deprecated and support will be removed.
     *
     * @return $this
     *
     * @see ResultIterator::hydrator()
     */
    public function setHydrator(callable $hydrator): self;

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
     *
     * @deprecated
     *   fetchColumn() implementation is very unperformant, it is not advised
     *   to use it. Whenever you need fetchColumn() instead of simply use the
     *   key column along with a custom hydrator returning a single value, you
     *   probably did something wrong.
     *   This will be removed in next major.
     */
    public function fetchColumn($name = null);

    /**
     * Get next element and move forward.
     *
     * @return mixed|Row
     *   Either a Row instance, or anything the hydrator will return if an
     *   hydrator is set.
     */
    public function fetch();
}
