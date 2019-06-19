<?php

declare(strict_types=1);

namespace Goat\Converter;

interface ValueConverterInterface
{
    /**
     * Get native PHP type
     *
     * @param string $type
     *
     * @return null|string
     */
    public function getPhpType(string $type, ConverterInterface $converter): ?string;

    /**
     * Can this value converter handle this type
     */
    public function isTypeSupported(string $type, ConverterInterface $converter): bool;

    /**
     * From the given raw SQL string, get the PHP value
     *
     * @param string $type
     * @param mixed $value
     *   This can't be type hinted, because some drivers will convert
     *   scalar types by themselves
     * @param ConverterInterface $converter
     *   Global converter, in case you need it to handle subtypes
     *
     * @return mixed
     */
    public function fromSQL(string $type, $value, ConverterInterface $converter);

    /**
     * From the given PHP value, get the raw SQL string
     *
     * @param string $type
     * @param mixed $value
     * @param ConverterInterface $converter
     *   Global converter, in case you need it to handle subtypes
     *
     * @return string
     */
    public function toSQL(string $type, $value, ConverterInterface $converter): ?string;

    /**
     * Can this value converter handle this value
     */
    public function guessType($value, ConverterInterface $converter): ?string;
}
