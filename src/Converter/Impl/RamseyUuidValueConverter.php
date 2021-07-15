<?php

declare(strict_types=1);

namespace Goat\Converter\Impl;

use Goat\Converter\ConverterContext;
use Goat\Converter\StaticInputValueConverter;
use Goat\Converter\StaticOutputValueConverter;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * UUID converter using ramsey/uuid.
 *
 * Simply cast values as boolean values.
 *
 * @see https://www.postgresql.org/docs/13/datatype-uuid.html
 */
class RamseyUuidConverter implements StaticInputValueConverter, StaticOutputValueConverter
{
    /**
     * {@inheritdoc}
     */
    public function supportedInputTypes(): array
    {
        return [
            UuidInterface::class =>  ['uuid'],
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
            'uuid' => UuidInterface::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $phpType, ?string $sqlType, string $value, ConverterContext $context)
    {
        return Uuid::fromString($value);
    }
}
