<?php

namespace Goat\Runner\Testing;

use GeneratedHydrator\Bridge\Symfony\DeepHydrator;
use GeneratedHydrator\Bridge\Symfony\DefaultHydrator;
use Goat\Driver\Configuration;
use Goat\Driver\Driver;
use Goat\Driver\DriverFactory;
use Goat\Runner\Runner;
use Goat\Runner\Hydrator\GeneratedHydratorBundleHydratorRegistry;
use Goat\Runner\Hydrator\HydratorRegistry;
use Goat\Runner\Metadata\ApcuResultMetadataCache;
use Goat\Runner\Metadata\ArrayResultMetadataCache;
use PHPUnit\Framework\SkippedTestError;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class TestDriverFactory
{
    /** @var string */
    private $driverName;
    /** @var string */
    private $envVarName;
    /** @var bool */
    private $apcuEnabled = false;
    /** @var string */
    private $schema;
    /** @var callable */
    private $initializer;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(string $driverName, string $envVarName, bool $apcuEnabled = false, ?callable $initializer = null)
    {
        $this->apcuEnabled = $apcuEnabled;
        $this->driverName = $driverName;
        $this->envVarName = $envVarName;
        $this->initializer = $initializer;
        $this->logger = new NullLogger();
        $this->schema = \uniqid('test_schema_');
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public static function isApcuEnabled(): bool
    {
        return \function_exists('apcu_add') && \getenv('ENABLE_APCU');
    }

    /** @return array[] */
    public static function getEnabledServers(): array
    {
        $ret = [];

        if (\getenv('ENABLE_PDO')) {
            $ret[] = [Configuration::DRIVER_PDO_MYSQL, 'MYSQL_57_URI'];
            $ret[] = [Configuration::DRIVER_PDO_MYSQL, 'MYSQL_80_URI'];
            $ret[] = [Configuration::DRIVER_PDO_PGSQL, 'PGSQL_95_URI'];
        }
        if (\getenv('ENABLE_EXT_PGSQL')) {
            $ret[] = [Configuration::DRIVER_EXT_PGSQL, 'PGSQL_95_URI'];
        }

        return $ret;
    }

    public function getDriver(): Driver
    {
        if (!$uri = \getenv($this->envVarName)) {
            throw new SkippedTestError(\sprintf("'%s' environment variable is missing.", $this->envVarName));
        }

        $configuration = Configuration::fromString($uri);
        $configuration->setLogger($this->logger);

        return DriverFactory::fromConfiguration($configuration);
    }

    private function createTestHydratorRegistry(): ?HydratorRegistry
    {
        if (\class_exists(DeepHydrator::class)) {
            return new GeneratedHydratorBundleHydratorRegistry(
                new DeepHydrator(
                    new DefaultHydrator(
                        \sys_get_temp_dir()
                    )
                )
            );
        }

        return null;
    }

    public function getRunner(?callable $initializer = null): Runner
    {
        $runner = $this->getDriver()->getRunner();

        if ($hydratorRegistry = $this->createTestHydratorRegistry()) {
            $runner->setHydratorRegistry($hydratorRegistry);
        }

        if ($this->apcuEnabled) {
            $runner->setResultMetadataCache(new ApcuResultMetadataCache());
        } else {
            $runner->setResultMetadataCache(new ArrayResultMetadataCache());
        }

        if ($runner->getPlatform()->supportsSchema()) {
            $this->createTestSchema($runner, $this->schema);
        }

        if ($this->initializer) {
            \call_user_func($this->initializer, $runner, $this->schema);
            $this->initializer = null;
        }
        if ($initializer) {
            \call_user_func($initializer, $runner, $this->schema);
        }

        return $runner;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    private function createTestSchema(Runner $runner, string $schema): void
    {
        if (!$runner->getPlatform()->supportsSchema()) {
            return;
        }
        $runner->execute(\sprintf('CREATE SCHEMA "%s"', $schema));
        // @todo this will only work with pgsql
        //   since it's the only database with schema support we do support
        //   it's OK for now.
        // We do not care about restoring the default schema search path,
        // this code is meant to run for a single test duration.
        $runner->execute(\sprintf('SET search_path TO "%s"', $schema));
    }
}
