<?php

declare(strict_types=1);

namespace Goat\Converter;

/**
 * Static input converter will be much faster to execute.
 */
interface StaticInputValueConverter extends InputValueConverter
{
    /**
     * Get supported value types.
     *
     * @return array<string,string[]>
     *   Keys are PHP types, values are SQL types.
     */
    public function supportedInputTypes(): array;
}
