<?php

declare(strict_types=1);

namespace Goat\Converter\Impl;

use Goat\Converter\ConverterContext;
use Goat\Converter\StaticInputValueConverter;
use Goat\Converter\StaticOutputValueConverter;
use Goat\Converter\TypeConversionError;

/**
 * Boolean converter.
 *
 * Simply cast values as boolean values.
 *
 * @see https://www.postgresql.org/docs/13/datatype-boolean.html
 */
class JsonValueConverter implements StaticInputValueConverter, StaticOutputValueConverter
{
    /**
     * {@inheritdoc}
     */
    public function supportedInputTypes(): array
    {
        return [
            'array' =>  ['json', 'jsonb'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value, ConverterContext $context): ?string
    {
        if (!\is_array($value) /* __json? */) {
            throw new TypeConversionError();
        }

        return \json_encode($value);
    }

    /**
     * {@inheritdoc}
     */
    public function supportedOutputTypes(): array
    {
        return [
            'json' => ['array'],
            'jsonb' => ['array'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $phpType, ?string $sqlType, string $value, ConverterContext $context)
    {
        return \json_decode($value, true);
    }
}
