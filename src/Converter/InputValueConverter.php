<?php

declare(strict_types=1);

namespace Goat\Converter;

/**
 * From PHP value to SQL value string converter.
 */
interface InputValueConverter
{
    /**
     * From the given PHP value, get the raw SQL string.
     *
     * @param string $sqlType
     *   Targeted SQL type. It can be a known alias.
     * @param mixed $value
     *   PHP value, type will be derived from value itself using introspection.
     * @param ConverterContext
     *   Current converter context.
     *
     * @return null|string
     *   SQL formated value.
     * @throws TypeConversionError
     *   If type conversion is not possible.
     */
    public function toSQL(string $sqlType, /* mixed */ $value, ConverterContext $context): ?string;
}
