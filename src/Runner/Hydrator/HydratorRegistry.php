<?php

declare(strict_types=1);

namespace Goat\Runner\Hydrator;

interface HydratorRegistry
{
    /**
     * Get hydrator for class name.
     *
     * @return callable
     *   Callable first argument will be object values array, keys being target
     *   PHP class property names. It must both create the instance and return
     *   the object.
     */
    public function getHydrator(string $className): callable;
}
