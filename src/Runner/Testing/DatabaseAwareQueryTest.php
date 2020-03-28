<?php

declare(strict_types=1);

namespace Goat\Runner\Testing;

use Goat\Runner\Runner;
use PHPUnit\Framework\TestCase;

abstract class DatabaseAwareQueryTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        \gc_collect_cycles();
    }

    /**
     * Data provider for using drivers.
     */
    public function driverDataProvider(): iterable
    {
        foreach (TestDriverFactory::getEnabledDrivers() as $driverName) {
            yield $driverName => [new TestDriverFactory($driverName)];
        }
    }

    /**
     * Data provider for using runners.
     *
     * During this generator execution, $this->runner and $this->driver will
     * be set, hopefully they will be properly closed during teardown, which
     * should happen after every test.
     */
    public function runnerDataProvider(): iterable
    {
        $useApcu = TestDriverFactory::isApcuEnabled();

        foreach (TestDriverFactory::getEnabledDrivers() as $driverName) {
            yield $driverName => [
                new TestDriverFactory(
                    $driverName,
                    false,
                    function (Runner $runner, string $schema) {
                       $this->createTestData($runner, $schema);
                    }
                ),
            ];

            if ($useApcu) {
                yield $driverName.'_apcu' => [
                    new TestDriverFactory(
                        $driverName,
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
     */
    protected function createTestData(Runner $runner, ?string $schema): void
    {
    }
}
