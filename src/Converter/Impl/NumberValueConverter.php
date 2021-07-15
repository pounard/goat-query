<?php

declare(strict_types=1);

namespace Goat\Converter\Impl;

use Goat\Converter\ConverterContext;
use Goat\Converter\StaticInputValueConverter;
use Goat\Converter\StaticOutputValueConverter;
use Goat\Converter\TypeConversionError;

/**
 * Number converter.
 *
 * This implementation is raw and doesn't type check input, it just does
 * explicit PHP cast magic relying upon type coercion, in order to ensure
 * best performances.
 *
 * @see https://www.postgresql.org/docs/13/datatype-numeric.html
 *
 * @todo
 *   - Add GMP types,
 *   - Add BCMath types ?
 *   - Add Moontoast types ?
 *   - Handle Infinity, -Infinity, NaN
 */
class NumberValueConverter implements StaticInputValueConverter, StaticOutputValueConverter
{
    public const NUMERIC_TYPES_SQL = [
        // Integer types.
        'smallint', // 2 bytes
        'integer', // 4 bytes
        'bigint', // 8 bytes
        // Decimal exact types.
        'decimal', // variable length
        'numeric', // variable length
        // Decimal inexact types.
        'real', // 4 bytes
        'double precision', // 8 bytes
        // Serial type, alias to integer.
        'smallserial', // 2 bytes
        'serial', // 4 bytes
        'bigserial', // 8 bytes
    ];

    public const NUMERIC_TYPES_SQL_INT = [
        'smallint' => true, // 2 bytes
        'integer' => true, // 4 bytes
        'bigint' => true, // 8 bytes
        'smallserial' => true, // 2 bytes
        'serial' => true, // 4 bytes
        'bigserial' => true, // 8 bytes
    ];

    public const NUMERIC_TYPES_PHP = [
        'int',
        'float',
    ];

    /**
     * {@inheritdoc}
     */
    public function supportedInputTypes(): array
    {
        return [
            'int' => self::NUMERIC_TYPES_SQL,
            'float' => self::NUMERIC_TYPES_SQL,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value, ConverterContext $context): ?string
    {
        if (!\is_scalar($value)) {
            throw new TypeConversionError("Given value must be int or float.");
        }

        if (\array_key_exists($type, self::NUMERIC_TYPES_SQL_INT)) {
            return (string) (int) $value;
        } else {
            return (string) (float) $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportedOutputTypes(): array
    {
        return [
            'bigint' => ['int', 'float'],
            'bigserial' => ['int', 'float'],
            'decimal' => ['float'],
            'double precision' => ['float'],
            'integer' => ['int', 'float'],
            'numeric' => ['float'],
            'real' => ['float'],
            'serial' => ['int', 'float'],
            'smallint' => ['int', 'float'],
            'smallserial' => ['int', 'float'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $phpType, ?string $sqlType, string $value, ConverterContext $context)
    {
        if (!\preg_match('/^\d+(|\.\d+)$/', $value)) {
            throw new TypeConversionError("Given input is not a number.");
        }

        if ('int' === $phpType) {
            return (int) $value;
        } else if ('float' === $phpType) {
            return (float) $value;
        }
    }
}
