<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Hydrator;

use Goat\Hydrator\HydratorInterface;
use Goat\Hydrator\Configuration\ClassConfiguration;
use Goat\Runner\Hydrator\ResultHydrator;
use Goat\Runner\Tests\Hydrator\Mock\HydratedClass;
use Goat\Runner\Tests\Hydrator\Mock\HydratedNestingClass;
use Goat\Runner\Tests\Hydrator\Mock\HydratedParentClass;
use Goat\Runner\Tests\Hydrator\Mock\RecursivelyHydratedClass;
use PHPUnit\Framework\TestCase;

final class ResultHydratorTest extends TestCase
{
    /**
     * Test object nesting with a stupid separator.
     */
    public function testNestingAsArrayWithLongSeparator(): void
    {
        $hydrator = new ResultHydrator(null, '__foo__');

        $values = [
            'ownProperty1' => 1,
            'ownProperty2' => 3,
            'nestedObject1__foo__foo' => 5,
            'nestedObject1__foo__bar' => 7,
            'nestedObject1__foo__someNestedInstance__foo__miaw' => 17,
            'nestedObject2__foo__miaw' => 11,
        ];

        self::assertSame(
            [
                'ownProperty1' => 1,
                'ownProperty2' => 3,
                'nestedObject1' => [
                    'foo' => 5,
                    'bar' => 7,
                    'someNestedInstance' => [
                        'miaw' => 17,
                    ],
                ],
                'nestedObject2' => [
                    'miaw' => 11,
                ],
            ],
            $hydrator->hydrate($values)
        );
    }

    /**
     * Test object nesting hydration up to 3 levels of hydration
     */
    public function testNestingAsArray(): void
    {
        $hydrator = new ResultHydrator();

        $values = [
            'ownProperty1' => 1,
            'ownProperty2' => 3,
            'nestedObject1.foo' => 5,
            'nestedObject1.bar' => 7,
            'nestedObject1.someNestedInstance.miaw' => 17,
            'nestedObject2.miaw' => 11,
        ];

        $hydrator = new ResultHydrator();

        self::assertSame(
            [
                'ownProperty1' => 1,
                'ownProperty2' => 3,
                'nestedObject1' => [
                    'foo' => 5,
                    'bar' => 7,
                    'someNestedInstance' => [
                        'miaw' => 17,
                    ],
                ],
                'nestedObject2' => [
                    'miaw' => 11,
                ],
            ],
            $hydrator->hydrate($values)
        );
    }

    public function testDeepNestingAsArray(): void
    {
        $values = [
            [
                'foo' => 11,
                'bar.foo' => 12,
                'bar.bar.foo' => 13,
                'bar.bar.bar.foo' => 14,
            ],
            [
                'foo' => 21,
                'bar.foo' => 22,
                'bar.bar.foo' => 23,
                'bar.bar.bar.foo' => 24,
            ],
        ];

        $hydrator = new ResultHydrator();

        self::assertSame(
            [
                'foo' => 11,
                'bar' => [
                    'foo' => 12,
                    'bar' => [
                        'foo' => 13,
                        'bar' => [
                            'foo' => 14,
                        ],
                    ]
                ],
            ],
            $hydrator->hydrate($values[0])
        );

        self::assertSame(
            [
                'foo' => 21,
                'bar' => [
                    'foo' => 22,
                    'bar' => [
                        'foo' => 23,
                        'bar' => [
                            'foo' => 24,
                        ],
                    ]
                ],
            ],
            $hydrator->hydrate($values[1])
        );
    }

    public function testExistingNullPropertyIsIgnored()
    {
        $hydrator = new ResultHydrator();

        $values = [
            'nestedObject1' => null,
            'nestedObject1.foo' => 5,
        ];

        $object = $hydrator->hydrate($values);

        self::assertSame(['nestedObject1' => ['foo' => 5]], $object);
    }

    public function testExistingEmptyArrayPropertyIsIgnored()
    {
        $hydrator = new ResultHydrator();

        $values = [
            'nestedObject1' => [],
            'nestedObject1.foo' => 5,
        ];

        $object = $hydrator->hydrate($values);

        self::assertSame(['nestedObject1' => ['foo' => 5]], $object);
    }

    public function testExistingEmptyStringPropertyIsIgnored()
    {
        $hydrator = new ResultHydrator();

        $values = [
            'nestedObject1' => '',
            'nestedObject1.foo' => 5,
        ];

        $object = $hydrator->hydrate($values);

        self::assertSame(['nestedObject1' => ['foo' => 5]], $object);
    }
}
