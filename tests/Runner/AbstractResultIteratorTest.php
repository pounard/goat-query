<?php

declare(strict_types=1);

namespace Goat\Runner\Tests;

use Goat\Converter\DefaultConverter;
use Goat\Hydrator\HydratorInterface;
use Goat\Query\QueryError;
use PHPUnit\Framework\TestCase;

/**
 * Test basics abstract result iterator behaviours
 */
final class AbstractResultIteratorTest extends TestCase
{
    /**
     * Foo result iterator
     */
    private function createArrayResultIterator()
    {
        return new ArrayResultIterator(
            [
                ['a', 'int'],
                ['b', 'varchar'],
                ['c', 'date']
            ],
            [
                [
                    'a' => '1',
                    'b' => 'foo',
                    'c' => '1983-03-22',
                ],
                [
                    'a' => '2',
                    'b' => 'bar',
                    'c' => '2012-01-12',
                ],
            ]
        );
    }

    /**
     * Without hydrator, rows will be arrays, values will be strings
     */
    public function testHydrationWithNothing()
    {
        $result = $this->createArrayResultIterator();
        $result->setConverter(new DefaultConverter());

        $row = $result->fetch();

        self::assertIsArray($row);
        self::assertSame(1, $row['a']);
        self::assertSame('foo', $row['b']);
        self::assertInstanceOf(\DateTimeInterface::class, $row['c']);
    }

    /**
     * With a callback, callback gets executed with the transformed row as input
     */
    public function testHydrationWithCallback()
    {
        $result = $this->createArrayResultIterator();
        $result->setConverter(new DefaultConverter());

        $result->setHydrator(function (array $row) {
            self::assertIsArray($row);
            self::assertSame(1, $row['a']);
            self::assertSame('foo', $row['b']);
            self::assertInstanceOf(\DateTimeInterface::class, $row['c']);

            return ['boo'];
        });

        $row = $result->fetch();

        self::assertSame(['boo'], $row);
    }

    /**
     * Hydrator gets called with row
     */
    public function testHydrationWithHydrator()
    {
        $result = $this->createArrayResultIterator();
        $result->setConverter(new DefaultConverter());

        $result->setHydrator(new class () implements HydratorInterface {

            public function createAndHydrateInstance(array $values, $constructor = self::CONSTRUCTOR_SKIP)
            {
                if (!$values['c'] instanceof \DateTimeInterface) {
                    throw new \LogicException("Values were not converted");
                }
                return ['baah'];
            }

            public function hydrateObject(array $values, $object)
            {
                throw new \LogicException("I shall not be called");
            }

            public function extractValues($object)
            {
                throw new \LogicException("I shall not be called");
            }
        });

        $row = $result->fetch();
        self::assertSame(['baah'], $row);
    }

    /**
     * You can just drop the hydrator
     */
    public function testSetNullHydratorIsOk()
    {
        $result = $this->createArrayResultIterator();
        $result->setHydrator(null);

        $row = $result->fetch();
        self::assertIsArray($row);
    }

    /**
     * You cannot set anything as hydrator
     */
    public function testSetStupidHydratorRaiseError()
    {
        $result = $this->createArrayResultIterator();

        self::expectException(QueryError::class);
        $result->setHydrator('foo');
    }

    /**
     * You cannot change hydrator once started.
     */
    public function testSetHydratorRaiseErrorWhenStarted()
    {
        $result = $this->createArrayResultIterator();
        $result->setConverter(new DefaultConverter());

        $result->fetch();

        self::expectException(QueryError::class);
        $result->setHydrator(null);
    }
}
