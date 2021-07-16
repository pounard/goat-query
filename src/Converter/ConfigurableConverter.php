<?php

declare(strict_types=1);

namespace Goat\Converter;

/**
 * Interface that allows registering user-given value converters.
 */
interface ConfigurableConverter extends Converter
{
    /**
     * Register a value converter.
     *
     * @param InputValueConverter|OutputValueConverter $instance
     */
    public function register(/* InputValueConverter|OutputValueConverter */ $instance): void;
}
