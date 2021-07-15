<?php

declare(strict_types=1);

namespace Goat\Converter\Impl;

use Goat\Converter\ConverterContext;
use Goat\Converter\StaticInputValueConverter;
use Goat\Converter\StaticOutputValueConverter;

/**
 * Text converter.
 *
 * This converter does not validate input, does not proceed to charset
 * conversion, only cast everything to string bothways. For the 'char'
 * type for example, it doesn't check string size.
 *
 * @see https://www.postgresql.org/docs/13/datatype-character.html
 *
 * @todo
 *  - Handle client connection charset conversion?
 */
class TextValueConverter implements StaticInputValueConverter, StaticOutputValueConverter
{
    /**
     * {@inheritdoc}
     */
    public function supportedInputTypes(): array
    {
        if (\class_exists(\Stringable::class)) {
            return [
                'float' =>  ['char', 'text', 'varchar'],
                'int' => ['char', 'text', 'varchar'],
                'string' => ['char', 'text', 'varchar'],
                \Stringable::class => ['char', 'text', 'varchar'],
            ];
        }

        return [
            'float' =>  ['char', 'text', 'varchar'],
            'int' => ['char', 'text', 'varchar'],
            'string' => ['char', 'text', 'varchar'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value, ConverterContext $context): ?string
    {
        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function supportedOutputTypes(): array
    {
        return [
            'char' => ['string'],
            'text' => ['string'],
            'varchar' => ['string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $phpType, ?string $sqlType, string $value, ConverterContext $context)
    {
        return $value;
    }
}
