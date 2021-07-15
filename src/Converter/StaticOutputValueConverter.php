<?php

declare(strict_types=1);

namespace Goat\Converter;

/**
 * Static output converter will be much faster to execute.
 */
interface StaticOutputValueConverter extends OutputValueConverter
{
    /**
     * Get supported value types.
     *
     * @return array<string,string[]>
     *   Keys are SQL types, values are PHP types.
     */
    public function supportedOutputTypes(): array;
}
