<?php

declare(strict_types=1);

namespace Goat\Driver\Tests;

use Goat\Driver\Configuration;
use Goat\Driver\PDODriver;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

final class PDODriverTest extends DatabaseAwareQueryTest
{
    public function pdoDriverDataProvider(): iterable
    {
        foreach (TestDriverFactory::getEnabledServers() as $serverInfo) {
            list($driverName, $envVarName) = $serverInfo;

            if (false !== \stripos($driverName, 'pdo-')) {
                yield [$envVarName];
            }
        }
    }

    /** @dataProvider pdoDriverDataProvider */
    public function testCreateFromPDO(string $envVarName): void
    {
        $serverUrl = \getenv($envVarName);

        if (!$serverUrl) {
            self::markTestSkipped(\sprintf("Missing env var %s value", $envVarName));
        }

        $configuration = Configuration::fromString($serverUrl);

        $remotePdoConnection = new \PDO(
            PDODriver::buildConnectionString($configuration->getOptions()),
            $configuration->getUsername(),
            $configuration->getPassword()
        );

        // Just ensure it does work.
        $remotePdoConnection->query("SELECT 1");

        $driver = PDODriver::createFromPDO($remotePdoConnection, $configuration->getDriver());

        self::assertSame(
            1,
            $driver->getRunner()->execute("SELECT 1")->fetchField()
        );
    }
}
