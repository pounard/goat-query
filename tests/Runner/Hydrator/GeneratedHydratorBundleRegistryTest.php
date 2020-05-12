<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Hydrator;

use Goat\Runner\Hydrator\DefaultHydratorRegistry;
use Goat\Runner\Hydrator\GeneratedHydratorBundleRegistry;
use Goat\Runner\Hydrator\HydratorRegistry;

class GeneratedHydratorBundleRegistryTest extends AbstractHydratorRegistryTest
{
    protected function createHydratorRegistry(): HydratorRegistry
    {
        $hydrator = DefaultHydratorRegistry::createDefaultInstance();

        if (!$hydrator instanceof GeneratedHydratorBundleRegistry) {
            self::markTestSkipped();
        }

        return $hydrator;
    }
}
