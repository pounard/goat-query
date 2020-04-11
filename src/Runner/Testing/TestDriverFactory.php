<?php

namespace Goat\Runner\Testing;

use GeneratedHydrator\Configuration as GeneratedHydratorConfiguration;
use Goat\Driver\Configuration;
use Goat\Driver\ConfigurationError;
use Goat\Driver\Driver;
use Goat\Driver\ExtPgSQLDriver;
use Goat\Driver\PDODriver;
use Goat\Driver\Runner\AbstractRunner;
use Goat\Hydrator\HydratorMap;
use Goat\Runner\Runner;
use Goat\Runner\Metadata\ApcuResultMetadataCache;
use Goat\Runner\Metadata\ArrayResultMetadataCache;
use PHPUnit\Framework\SkippedTestError;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class TestDriverFactory
{
    /** @var string */
    private $driverName;

    /** @var bool */
    private $apcuEnabled = false;

    /** @var string */
    private $schema;

    /** @var callable */
    private $initializer;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(string $driverName, bool $apcuEnabled = false, ?callable $initializer = null)
    {
        $this->apcuEnabled = $apcuEnabled;
        $this->driverName = $driverName;
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

    private function createConfiguration(string $prefix, string $driver): Configuration
    {
        if ($hostname = \getenv($prefix.'_HOSTNAME')) {
            $database = \getenv($prefix.'_DATABASE');
            $username = \getenv($prefix.'_PASSWORD');
            $password = \getenv($prefix.'_USERNAME');
        } else {
            throw new SkippedTestError(\sprintf("$prefix.'_HOSTNAME' environment variable is missing."));
        }

        $configuration = new Configuration([
            'database' => $database,
            'driver' => $driver,
            'host' => $hostname,
            'password' => $password,
            'username' => $username,
        ]);
        $configuration->setLogger($this->logger);

        return $configuration;
    }

    protected function createExtPgSQLDriver(): ExtPgSQLDriver
    {
        $configuration = $this->createConfiguration('PGSQL', 'pgsql');

        $driver = new ExtPgSQLDriver();
        $driver->setConfiguration($configuration);

        return $driver;
    }

    protected function createPDOMySQLDriver(): PDODriver
    {
        $configuration = $this->createConfiguration('MYSQL', 'mysql');

        $driver = new PDODriver();
        $driver->setConfiguration($configuration);

        return $driver;
    }

    protected function createPDOPgSQLDriver(): PDODriver
    {
        $configuration = $this->createConfiguration('PGSQL', 'pgsql');

        $driver = new PDODriver();
        $driver->setConfiguration($configuration);

        return $driver;
    }

    public static function isApcuEnabled(): bool
    {
        return \function_exists('apcu_add') && \getenv('ENABLE_APCU');
    }

    /** @return string[] */
    public static function getEnabledDrivers(): array
    {
        $ret = [];
        if (\getenv('ENABLE_PDO')) {
            $ret[] = 'pdo_mysql';
            $ret[] = 'pdo_pgsql';
        }
        if (\getenv('ENABLE_EXT_PGSQL')) {
            $ret[] = 'ext_pgsql';
        }
        return $ret;
    }

    public function getDriver(): Driver
    {
        switch ($this->driverName) {

            case 'pdo_mysql':
                return $this->createPDOMySQLDriver();

            case 'pdo_pgsql':
                return $this->createPDOPgSQLDriver();

            case 'ext_pgsql':
                return $this->createExtPgSQLDriver();

            default:
                throw new ConfigurationError(\sprintf("Invalid driver name: '%s'", $this->driverName));
        }
    }

    public function getRunner(?callable $initializer = null): Runner
    {
        $runner = $this->getDriver()->getRunner();

        if ($runner instanceof AbstractRunner && \class_exists(HydratorMap::class)) {
            $runner->setHydratorMap(new HydratorMap(new GeneratedHydratorConfiguration()));
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
