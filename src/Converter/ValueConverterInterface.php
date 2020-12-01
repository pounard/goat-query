<?php

declare(strict_types=1);

namespace Goat\Converter;

interface ValueConverterInterface
{
    /**
     * Can this value converter handle this type.
     */
    public function isTypeSupported(string $type, ConverterContext $context): bool;

    /**
     * From the given raw SQL string, get the PHP value.
     */
    public function fromSQL(string $type, /* null|int|float|string */ $value, ConverterContext $context) /* : mixed */;

    /**
     * From the given PHP value, get the raw SQL string.
     */
    public function toSQL(string $type, /* mixed */ $value, ConverterContext $context): ?string;

    /**
     * Guess SQL type for the given value.
     */
    public function guessType($value, ConverterContext $context): string;
}
