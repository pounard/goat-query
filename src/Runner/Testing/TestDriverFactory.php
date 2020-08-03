<?php

namespace Goat\Runner\Testing;

use Goat\Driver\Configuration;
use Goat\Driver\Driver;
use Goat\Driver\DriverFactory;
use Goat\Runner\Runner;
use Goat\Runner\Metadata\ApcuResultMetadataCache;
use Goat\Runner\Metadata\ArrayResultMetadataCache;
use PHPUnit\Framework\SkippedTestError;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class TestDriverFactory
{
    private string $driverName;
    private string $envVarName;
    private bool $apcuEnabled = false;
    private string $schema;
    private bool $schemaCreated = false;
    private LoggerInterface $logger;
    /** @var null|callable */
    private $initializer = null;

    public function __construct(string $driverName, string $envVarName, bool $apcuEnabled = false, ?callable $initializer = null)
    {
        $this->apcuEnabled = $apcuEnabled;
        $this->driverName = $driverName;
        $this->envVarName = $envVarName;
        $this->initializer = $initializer;
        $this->logger = new NullLogger();
        $this->schema = self::getEnv('TEST_SCHEMA') ?? \uniqid('test_schema_');
    }

    private static function getEnv(string $name, bool $withFallback = false): ?string
    {
        $realName = 'GOAT_' . $name;
        $value = \getenv($realName);

        if (false !== $value) {
            return $value;
        }

        if (!$withFallback) {
            return null;
        }

        $legacyName = $name;
        $fallbackValue = \getenv($legacyName);

        if (false !== $fallbackValue) {
            @\trigger_error(\sprintf("'%s' variable is now named '%s', you should consider fixing your phpunit.xml file.", $legacyName, $realName), E_USER_DEPRECATED);

            return $fallbackValue;
        }

        return null;
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
        return \function_exists('apcu_add') && self::getEnv('ENABLE_APCU', true);
    }

    /** @return array[] */
    public static function getEnabledServers(): array
    {
        $ret = [];

        if (self::getEnv('ENABLE_PDO', true)) {
            $ret[] = [Configuration::DRIVER_PDO_MYSQL, 'MYSQL_57_URI'];
            $ret[] = [Configuration::DRIVER_PDO_MYSQL, 'MYSQL_80_URI'];
            $ret[] = [Configuration::DRIVER_PDO_PGSQL, 'PGSQL_95_URI'];
        }
        if (self::getEnv('ENABLE_EXT_PGSQL', true)) {
            $ret[] = [Configuration::DRIVER_EXT_PGSQL, 'PGSQL_95_URI'];
        }

        return $ret;
    }

    public function getDriver(): Driver
    {
        if (!$uri = self::getEnv($this->envVarName, true)) {
            throw new SkippedTestError(\sprintf("'%s' environment variable is missing.", $this->envVarName));
        }

        $configuration = Configuration::fromString($uri, ['driver' => $this->driverName]);
        $configuration->setLogger($this->logger);

        return DriverFactory::fromConfiguration($configuration);
    }

    public function getRunner(?callable $initializer = null): Runner
    {
        $runner = $this->getDriver()->getRunner();

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

    public function getDriverName(): string
    {
        return $this->driverName;
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
        if ($this->schemaCreated) {
            return;
        }

        $runner->execute(\sprintf('CREATE SCHEMA "%s"', $schema));
        // @todo this will only work with pgsql
        //   since it's the only database with schema support we do support
        //   it's OK for now.
        // We do not care about restoring the default schema search path,
        // this code is meant to run for a single test duration.
        $runner->execute(\sprintf('SET search_path TO "%s"', $schema));

        $this->schemaCreated = true;
    }
}
