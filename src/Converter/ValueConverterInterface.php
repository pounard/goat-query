<?php

declare(strict_types=1);

namespace Goat\Converter;

interface ValueConverterInterface
{
    /**
     * Get handled types
     *
     * @return string[]
     *   Type names
     */
    public function getHandledTypes(): array;

    /**
     * Get native PHP type
     *
     * @param string $type
     *
     * @return null|string
     */
    public function getPhpType(string $type): ?string;

    /**
     * From the given raw SQL string, get the PHP value
     *
     * @param string $type
     * @param mixed $value
     *   This can't be type hinted, because some drivers will convert
     *   scalar types by themselves
     *
     * @return mixed
     */
    public function fromSQL(string $type, $value);

    /**
     * From the given PHP value, get the raw SQL string
     *
     * @param string $type
     * @param mixed $value
     *
     * @return string
     */
    public function toSQL(string $type, $value) : ?string;

    /**
     * Can this value be processed
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function canProcess($value) : bool;
}
