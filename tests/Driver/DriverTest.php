<?php

declare(strict_types=1);

namespace Goat\Driver\Tests;

use Goat\Driver\Platform\Platform;
use Goat\Runner\Runner;
use Goat\Runner\ServerError;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;
use Goat\Runner\Testing\ArrayLogger;
use Psr\Log\LogLevel;

final class DriverTest extends DatabaseAwareQueryTest
{
    /** @dataProvider driverDataProvider */
    public function testGetPlatform(TestDriverFactory $factory): void
    {
        $driver = $factory->getDriver();

        $platform = $driver->getPlatform();

        self::assertInstanceOf(Platform::class, $platform);
    }

    /** @dataProvider driverDataProvider */
    public function testGetRunner(TestDriverFactory $factory): void
    {
        $driver = $factory->getDriver();

        $runner = $driver->getRunner();

        self::assertInstanceOf(Runner::class, $runner);
    }

    /** @dataProvider driverDataProvider */
    public function testRunQueriesAfterCloseRaiseError(TestDriverFactory $factory): void
    {
        $driver = $factory->getDriver();

        if (!$driver->canBeClosedProperly()) {
            self::markTestSkipped("This driver cannot close connections properly.");
        }

        $runner = $driver->getRunner();
        $driver->close();

        self::expectException(ServerError::class);
        $runner->execute("SELECT 1");
    }

    /** @dataProvider driverDataProvider */
    public function testGetRunQueriesAfterCloseRaiseError(TestDriverFactory $factory): void
    {
        self::markTestIncomplete("Implement me properly");

        $driver = $factory->getDriver();

        $driver->close();

        self::expectException(ServerError::class);
        $runner = $driver->getRunner();
        $runner->execute("SELECT 1");
    }

    /** @dataProvider driverDataProvider */
    public function testDriverLogsConnectionAndDisconnection(TestDriverFactory $factory): void
    {
        $logger = new ArrayLogger();
        $factory->setLogger($logger);

        $driver = $factory->getDriver();

        self::assertSame(0, $logger->getMessageCount(LogLevel::INFO));

        $driver->connect();
        self::assertSame(1, $logger->getMessageCount(LogLevel::INFO));

        $driver->close();
        self::assertSame(2, $logger->getMessageCount(LogLevel::INFO));
    }

    /** @dataProvider driverDataProvider */
    public function testDriverPropagatesLoggerToRunner(TestDriverFactory $factory): void
    {
        $logger = new ArrayLogger();
        $factory->setLogger($logger);

        $driver = $factory->getDriver();

        self::assertSame(0, $logger->getMessageCount(LogLevel::WARNING));

        $driver
            ->getRunner()
            // Force an error to trigger, else we won't have messages.
            // You can't have both an hydrator and a class.
            ->execute("SELECT 1", [], [
                'class' => \DateTime::class,
                'hydrator' => static function () {}
            ])
        ;

        self::assertSame(1, $logger->getMessageCount(LogLevel::WARNING));
    }
}
