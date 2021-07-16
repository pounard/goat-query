<?php

declare(strict_types=1);

namespace Goat\Converter\Impl;

use Goat\Converter\ConverterContext;
use Goat\Converter\StaticInputValueConverter;
use Goat\Converter\StaticOutputValueConverter;

/**
 * Boolean converter.
 *
 * Simply cast values as boolean values.
 *
 * @see https://www.postgresql.org/docs/13/datatype-boolean.html
 */
class BooleanValueConverter implements StaticInputValueConverter, StaticOutputValueConverter
{
    /**
     * {@inheritdoc}
     */
    public function supportedInputTypes(): array
    {
        return [
            '*' =>  ['boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value, ConverterContext $context): ?string
    {
        return $value ? 'true' : 'false';
    }

    /**
     * {@inheritdoc}
     */
    public function supportedOutputTypes(): array
    {
        return [
            'boolean' => ['bool'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $phpType, ?string $sqlType, string $value, ConverterContext $context)
    {
        // When working with Doctrine driver, some types are already converted
        if (\is_bool($value)) {
            return $value;
        }
        if (!$value || 'f' === $value || 'F' === $value || 'false' === \strtolower($value)) {
            return false;
        }
        return (bool) $value;
    }
}
