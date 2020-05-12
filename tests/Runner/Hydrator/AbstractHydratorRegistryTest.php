<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Hydrator;

use Goat\Runner\Hydrator\HydratorRegistry;
use Goat\Runner\Tests\Hydrator\Mock\HydratedClass;
use PHPUnit\Framework\TestCase;

abstract class AbstractHydratorRegistryTest extends TestCase
{
    protected abstract function createHydratorRegistry(): HydratorRegistry;

    public function testParentHydration(): void
    {
        $values = [
            'miaw' => 1,
            'foo' => 3,
            'bar' => 'foo',
        ];

        $hydrator = $this
            ->createHydratorRegistry()
            ->getHydrator(HydratedClass::class)
        ;

        $object = $hydrator($values);

        self::assertInstanceOf(HydratedClass::class, $object);
        self::assertSame(1, $object->getMiaw()); // Should get parent's property.
        self::assertSame(3, $object->getFoo());
        self::assertSame('foo', $object->getBar());
    }
}
