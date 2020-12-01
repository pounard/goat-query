<?php

declare(strict_types=1);

namespace Goat\Converter;

/**
 * Converter is created by a runner for its own platform/driver couple.
 *
 * Because users might want to register project-wide custom value converters
 * we expose the public setValueConverterRegistry() on both this interface
 * and the Runner one.
 */
interface ConverterInterface extends ValueConverterInterface
{
    const TYPE_NULL = 'null';
    const TYPE_UNKNOWN = '_';

    /**
     * Set value converter registry.
     *
     * @internal
     *   Only the runner should call this method.
     */
    public function setValueConverterRegistry(ValueConverterRegistry $valueConverterRegistry): void;
}
