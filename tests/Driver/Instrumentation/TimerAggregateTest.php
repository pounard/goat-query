<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Driver\Instrumentation\TimerAggregate;
use PHPUnit\Framework\TestCase;

final class TimerAggregateTest extends TestCase
{
    public function testGetMethodsOnPendingWillNotCrash(): void
    {
        $timer = TimerAggregate::start();

        self::assertFalse($timer->isError());
        self::assertIsInt($timer->getTotalTime());
        self::assertSame(TimerAggregate::ERROR, $timer->get('foo'));
        self::assertSame([], $timer->getAll());
    }

    public function testGetMethodsAfterEnd(): void
    {
        $timer = TimerAggregate::start();
        $timer->stop();

        self::assertFalse($timer->isError());
        self::assertIsInt($timer->getTotalTime());
        self::assertSame(TimerAggregate::ERROR, $timer->get('foo'));
        self::assertSame([], $timer->getAll());
    }

    public function testBeginTwiceSetError(): void
    {
        $timer = TimerAggregate::start();
        $timer->begin('foo');

        self::assertFalse($timer->isError());

        $timer->begin('foo');

        self::assertTrue($timer->isError());
    }

    public function testEndNonExistingSetError(): void
    {
        $timer = TimerAggregate::start();

        self::assertFalse($timer->isError());

        $timer->end('foo');

        self::assertTrue($timer->isError());
    }

    public function testEndTwiceSetError(): void
    {
        $timer = TimerAggregate::start();
        $timer->begin('foo');

        self::assertFalse($timer->isError());

        $timer->end('foo');

        self::assertFalse($timer->isError());

        $timer->end('foo');

        self::assertTrue($timer->isError());
    }

    public function testGetAndGetAll(): void
    {
        $timer = TimerAggregate::start();
        $timer->begin('foo');
        $timer->begin('bar');

        self::assertFalse($timer->isError());

        $timer->end('foo');

        $timer->stop();

        $all = $timer->getAll();

        self::assertSame(['foo', 'bar'], \array_keys($all));
        self::assertIsInt($all['foo']);
        self::assertNotSame(TimerAggregate::ERROR, $all['foo']);
        self::assertIsInt($all['bar']);
        self::assertNotSame(TimerAggregate::ERROR, $all['bar']);

        self::assertNotSame(TimerAggregate::ERROR, $timer->get('foo'));
        self::assertNotSame(TimerAggregate::ERROR, $timer->get('bar'));
    }

    public function testGetNonExistingReturnError(): void
    {
        $timer = TimerAggregate::start();
        $timer->stop();

        self::assertSame(TimerAggregate::ERROR, $timer->get('foo'));
    }

    public function testEmptyTimer(): void
    {
        $timer = TimerAggregate::empty();

        self::assertSame(0, $timer->getTotalTime());
        self::assertSame([], $timer->getAll());
        self::assertFalse($timer->isError());
    }
}
