<?php

declare(strict_types=1);

namespace Goat\Converter;

/**
 * Dynamic output converter will be much slower to execute.
 */
interface DynamicOutputValueConverter extends OutputValueConverter
{
    /**
     * Can this output value converter handle the given value.
     */
    public function supportsOutput(?string $phpType, ?string $sqlType, string $value): bool;
}
