<?php

declare(strict_types=1);

namespace Goat\Runner\Tests;

use Goat\Converter\Tests\WithConverterTestTrait;
use Goat\Query\QueryError;
use Goat\Runner\Row;
use MakinaCorpus\Profiling\Implementation\DefaultProfiler;
use PHPUnit\Framework\TestCase;

/**
 * Test basics abstract result iterator behaviours
 */
final class AbstractResultIteratorTest extends TestCase
{
    use WithConverterTestTrait;

    /**
     * Foo result iterator
     */
    private function createArrayResultIterator(): ArrayResultIterator
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

    public function testCount(): void
    {
        $result = $this->createArrayResultIterator();

        self::assertSame(2, $result->count());
        self::assertSame(2, $result->count());
        self::assertSame(2, $result->countRows());
        self::assertSame(2, $result->countRows());

        self::assertSame(1, $result->getFetchRowCountCallCount());
    }

    public function testRewindableIteratorFetch(): void
    {
        $result = $this->createArrayResultIterator()->setRewindable(true);

        self::assertSame(['a' => '1', 'b' => 'foo', 'c' => '1983-03-22'], $result->fetch()->toArray());
        self::assertSame(['a' => '2', 'b' => 'bar', 'c' => '2012-01-12'], $result->fetch()->toArray());

        $result->rewind();

        self::assertSame(['a' => '1', 'b' => 'foo', 'c' => '1983-03-22'], $result->fetch()->toArray());
        self::assertSame(['a' => '2', 'b' => 'bar', 'c' => '2012-01-12'], $result->fetch()->toArray());
    }

    public function testNonRewindableIteratorFetch(): void
    {
        $result = $this->createArrayResultIterator();

        self::assertSame(['a' => '1', 'b' => 'foo', 'c' => '1983-03-22'], $result->fetch()->toArray());
        self::assertSame(['a' => '2', 'b' => 'bar', 'c' => '2012-01-12'], $result->fetch()->toArray());
    }

    public function testSetDebug(): void
    {
        self::expectNotToPerformAssertions();

        $result = $this->createArrayResultIterator();
        $result->setDebug(true);
    }

    public function testSetQueryProfiler(): void
    {
        $profiler = new DefaultProfiler();

        $result = $this->createArrayResultIterator();
        $result->setQueryProfiler($profiler);

        self::assertSame($profiler, $result->getQueryProfiler());
    }

    /**
     * Without hydrator, rows will be arrays, values will be strings
     */
    public function testHydrationWithNothing()
    {
        $result = $this->createArrayResultIterator();
        $result->setConverterContext(self::context());

        $row = $result->fetch()->toHydratedArray();

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
        $result->setConverterContext(self::context());

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
        $result->setConverterContext(self::context());

        $result->setHydrator(static function (array $values) {
            if (!$values['c'] instanceof \DateTimeInterface) {
                throw new \LogicException("Values were not converted");
            }
            return ['baah'];
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
        $result->setHydrator(fn (Row $row) => $row->toArray());

        $row = $result->fetch();

        self::assertIsArray($row);
    }

    /**
     * You cannot change hydrator once started.
     */
    public function testSetHydratorRaiseErrorWhenStarted()
    {
        $result = $this->createArrayResultIterator();
        $result->setConverterContext(self::context());

        $result->fetch();

        self::expectException(QueryError::class);

        $result->setHydrator(fn () => null);
    }
}
