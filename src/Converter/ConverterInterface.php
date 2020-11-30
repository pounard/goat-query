<?php

declare(strict_types=1);

namespace Goat\Converter;

/**
 * Public facade for the converter subsystem
 */
interface ConverterInterface
{
    const TYPE_NULL = 'null';
    const TYPE_UNKNOWN = '_';

    /**
     * Get client time zone.
     *
     * @deprecated
     *   This exists to workaround wrong date behavior, but will be removed in
     *   next major that will refactor the whole Converter namespace.
     */
    public function getClientTimeZone(): string;

    /**
     * Set client time zone, null resets.
     *
     * @deprecated
     *   This exists to workaround wrong date behavior, but will be removed in
     *   next major that will refactor the whole Converter namespace.
     */
    public function setClientTimeZone(?string $clientTimeZone = null): void;

    /**
     * Get native PHP type
     *
     * @param string $type
     *
     * @return null|string
     */
    public function getPhpType(string $sqlType): ?string;

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
    public function toSQL(string $type, $value): ?string;

    /**
     * Guess type for the given value
     *
     * @param mixed $value
     *
     * @return string
     */
    public function guessType($value): string;
}
