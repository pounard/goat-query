<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Driver\Platform\Platform;
use Goat\Runner\Runner;
use Goat\Runner\ServerError;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

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
    public function testGetRunerAfterCloseRaiseError(TestDriverFactory $factory): void
    {
        self::markTestIncomplete("Implement me properly");

        $driver = $factory->getDriver();

        $driver->close();

        self::expectException(ServerError::class);
        $runner = $driver->getRunner();
        $runner->execute("SELECT 1");
    }
}
