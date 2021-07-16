<?php

declare(strict_types=1);

namespace Goat\Converter;

/**
 * Converter is created by a runner for its own platform/driver couple.
 */
interface Converter
{
    /**
     * Convert the given SQL value string representing the given SQL type to
     * the given PHP type.
     *
     * @param null|int|float|string $value
     *   SQL raw value. In theory, we should always receive a string, but some
     *   drivers will convert automatically int and float values to PHP types.
     * @param string $sqlType
     *   Output SQL type if known. It can be a known alias. In the rare case
     *   you don't know which type it is, it will be treated as 'varchar'.
     * @param ?string $phpType
     *   The user expected PHP type. If none given, first converter that
     *   matches the given SQL type will be used.
     * @param ConverterContext
     *   Current converter context.
     *
     * @return null|mixed
     *   Anything typed as expected with $phpType type, null if value was null.
     * @throws TypeConversionError
     *   If type conversion is not possible.
     */
    public function fromSQL(/* null|int|float|string */ $value, ?string $sqlType, ?string $phpType, ?ConverterContext $context = null) /* : mixed */;

    /**
     * Convert the given PHP type to a valid SQL string for the given SQL type.
     *
     * @param mixed $value
     *   PHP value, type will be derived from value itself using introspection.
     * @param null|string $sqlType
     *   Targeted SQL type. It can be a known alias. if none given, the first
     *   output value converter registered for the given PHP type will be used.
     * @param ConverterContext
     *   Current converter context.
     *
     * @return null|string
     *   SQL formated value.
     * @throws TypeConversionError
     *   If type conversion is not possible.
     */
    public function toSQL(/* mixed */ $value, ?string $sqlType, ?ConverterContext $context = null): ?string;
}
