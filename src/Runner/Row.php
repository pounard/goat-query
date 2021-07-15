<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterContext;
use Goat\Converter\TypeConversionError;
use Goat\Runner\Metadata\ResultMetadata;

/**
 * Single row ow fetched from a result iterator.
 *
 * ArrayAccess interface only exists for backward-compatibility purpose, you
 * should avoid using it since it will be removed in the next major.
 */
interface Row extends \ArrayAccess
{
    /**
     * Fetch and an hydrated value using given PHP type.
     *
     * PHP type can be either a class name or a scalar type name. Converter
     * will be triggered and find the appropriate SQL to PHP converter
     * depending upon the RDBMS given type name and the user given PHP type
     * name.
     *
     * Specifying the PHP type is the only way to make your code type safe
     * It may also make hydration faster in certain cases.
     *
     * @return null|mixed
     *   The converted/hydrated value.
     *
     * @throws InvalidDataAccessError
     *   If column name does not exists.
     * @throws TypeConversionError
     *   If SQL to PHP type converter does not exist.
     */
    public function get(/* int|string */ $nameOrIndex, ?string $phpType = null); /* : mixed */

    /**
     * Does the value exists.
     */
    public function has(/* int|string */ $name): bool;

    /**
     * Get the raw unconverted value from SQL result.
     */
    public function raw(/* int|string */ $name) /* null|int|float|string */;

    /**
     * Get result metadata from result iterator.
     */
    public function getResultMetadata(): ResultMetadata;

    /**
     * Get converter context.
     */
    public function getConverterContext(): ConverterContext;

    /**
     * Return raw values as array.
     *
     * @return array<string,null|string>
     *   Keys are column names, values are SQL raw string values.
     */
    public function toArray(): array;

    /**
     * Return hydrated values as array.
     *
     * Warning, this will trigger the converter for expanding values, expected
     * values PHP types will be automatically guessed using a best effort kind
     * of algorithm: this is not type safe since it may yield unexpected
     * results.
     *
     * Is is recommended that you give an hydrator to the result instance, and
     * use this class get() method for fetching values and specify the expected
     * PHP type at the same time, to achieve type-safe code.
     *
     * Because this method can be convenient in some contexte, such as highly
     * dynamic database introspection tools, it will not be deprecated and be
     * kept as maintained and public API.
     *
     * @return array<string,null|mixed>
     *   Keys are column names, values are hydrated values.
     */
    public function toHydratedArray(): array;
}
