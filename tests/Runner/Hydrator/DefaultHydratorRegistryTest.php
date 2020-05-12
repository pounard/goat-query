<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Hydrator;

use Goat\Runner\Hydrator\DefaultHydratorRegistry;
use Goat\Runner\Hydrator\HydratorRegistry;

class DefaultHydratorRegistryTest extends AbstractHydratorRegistryTest
{
    protected function createHydratorRegistry(): HydratorRegistry
    {
        return new DefaultHydratorRegistry();
    }
}
