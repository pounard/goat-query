<?php

declare(strict_types=1);

namespace Goat\Runner\Testing;

use Goat\Runner\Runner;
use PHPUnit\Framework\TestCase;

abstract class DatabaseAwareQueryTest extends TestCase
{
    /**
     * Data provider for using drivers.
     */
    public function driverDataProvider(): iterable
    {
        foreach (TestDriverFactory::getEnabledServers() as $data) {
            list($driverName, $envVarName) = $data;

            yield $driverName .'_' . $envVarName => [new TestDriverFactory($driverName, $envVarName)];
        }
    }

    /**
     * Data provider for using runners.
     */
    public function runnerDataProvider(): iterable
    {
        $useApcu = TestDriverFactory::isApcuEnabled();

        foreach (TestDriverFactory::getEnabledServers() as $data) {
            list($driverName, $envVarName) = $data;

            yield $driverName .'_' . $envVarName  => [
                new TestDriverFactory(
                    $driverName,
                    $envVarName,
                    false,
                    function (Runner $runner, string $schema) {
                       $this->createTestData($runner, $schema);
                    }
                ),
            ];

            if ($useApcu) {
                yield $driverName .'_' . $envVarName . '_apcu' => [
                    new TestDriverFactory(
                        $driverName,
                        $envVarName,
                        true,
                        function (Runner $runner, string $schema) {
                            $this->createTestData($runner, $schema);
                        }
                    ),
                ];
            }
        }
    }

    /**
     * Override this method to create your test data.
     *
     * Be careful, when executing this method, $this context is a temporary
     * object used by the data provider to create the data, which means that
     * it will NOT be your own test class instance.
     */
    protected function createTestData(Runner $runner, ?string $schema): void
    {
    }
}
