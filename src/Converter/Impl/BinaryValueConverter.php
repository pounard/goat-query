<?php

declare(strict_types=1);

namespace Goat\Converter\Impl;

use Goat\Converter\ConverterContext;
use Goat\Converter\StaticInputValueConverter;
use Goat\Converter\StaticOutputValueConverter;

/**
 * Binary converter.
 *
 * Some low level drivers may give back streams or escaped strings
 * when requesting blob/bytea values. Blob as stream is handled in
 * generic \Goat\Driver\Runner\RunnerConverter implementation.
 * (un)escaping is done by Escaper instance that the platform gave
 * to the runner.
 *
 * This converter does not validate input, does not proceed to charset
 * conversion, only cast everything to string bothways. For the 'char'
 * type for example, it doesn't check string size.
 *
 * @see https://www.postgresql.org/docs/13/datatype-binary.html
 */
class BinaryValueConverter implements StaticInputValueConverter, StaticOutputValueConverter
{
    /**
     * {@inheritdoc}
     */
    public function supportedInputTypes(): array
    {
        if (\class_exists(\Stringable::class)) {
            return [
                'float' =>  ['bytea'],
                'int' => ['bytea'],
                'string' => ['bytea'],
                \Stringable::class => ['bytea'],
            ];
        }

        return [
            'float' =>  ['bytea'],
            'int' => ['bytea'],
            'string' => ['bytea'],
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
        if (\class_exists(\Stringable::class)) {
            return [
                'bytea' => ['float', 'int', 'string', \Stringable::class],
            ];
        }

        return [
            'bytea' => ['float', 'int', 'string'],
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
