<?php

declare(strict_types=1);

namespace Goat\Converter;

/**
 * Dynamic input converter will be much slower to execute.
 */
interface DynamicInputValueConverter extends InputValueConverter
{
    /**
     * Can this input value converter handle the given value.
     */
    public function supportsInput(string $sqlType, /* mixed */ $value): bool;
}
