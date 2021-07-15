<?php

declare(strict_types=1);

namespace Goat\Converter;

/**
 * From SQL value string to PHP value converter.
 */
interface OutputValueConverter
{
    /**
     * From the given raw SQL string, get the PHP value.
     *
     * @param string $phpType
     *   The user expected PHP type.
     * @param ?string $sqlType
     *   Output SQL type if known. It can be a known alias.
     * @param null|resource|string $value
     *   SQL raw value. It can be a string, null or a resource identifier.
     * @param ConverterContext
     *   Current converter context.
     *
     * @return null|mixed
     *   Anything typed as expected with $phpType type, null if value was null.
     * @throws TypeConversionError
     *   If type conversion is not possible.
     */
    public function fromSQL(string $phpType, ?string $sqlType, string $value, ConverterContext $context) /* : mixed */;
}
