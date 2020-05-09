<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Driver\Instrumentation\Timer;
use Goat\Query\QueryError;
use PHPUnit\Framework\TestCase;

final class TimerTest extends TestCase
{
    public function testGetTotalTimeWithoutStop(): void
    {
        $timer = new Timer();

        self::assertNotNull($timer->getTotalTime());
    }

    public function testGetTotalTimeWithStop(): void
    {
        $timer = new Timer();
        $timer->stop();

        self::assertNotNull($timer->getTotalTime());
    }

    public function testStopTwiceRaiseError(): void
    {
        $timer = new Timer();
        $timer->stop();

        self::expectException(QueryError::class);
        $timer->stop();
    }
}
