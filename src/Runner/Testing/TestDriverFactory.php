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

    public function __construct(string $driverName, bool $apcuEnabled = false, ?callable $initializer = null)
    {
        $this->apcuEnabled = $apcuEnabled;
        $this->driverName = $driverName;
        $this->initializer = $initializer;
        $this->schema = \uniqid('test_schema_');
    }

    protected static function createExtPgSQLDriver(): ExtPgSQLDriver
    {
        if ($hostname = \getenv('PGSQL_HOSTNAME')) {
            $database = \getenv('PGSQL_DATABASE');
            $username = \getenv('PGSQL_PASSWORD');
            $password = \getenv('PGSQL_USERNAME');
        } else {
            self::markTestSkipped(\sprintf("'PGSQL_HOSTNAME' environment variable is missing."));
        }

        $driver = new ExtPgSQLDriver();
        $driver->setConfiguration(new Configuration([
            'database' => $database,
            'driver' => 'pgsql',
            'host' => $hostname,
            'password' => $password,
            'username' => $username,
        ]));

        return $driver;
    }

    protected static function createPDOMySQLDriver(): PDODriver
    {
        if ($hostname = \getenv('MYSQL_HOSTNAME')) {
            $database = \getenv('MYSQL_DATABASE');
            $username = \getenv('MYSQL_USERNAME');
            $password = \getenv('MYSQL_PASSWORD');
        } else {
            self::markTestSkipped(\sprintf("'MYSQL_HOSTNAME' environment variable is missing."));
        }

        $driver = new PDODriver();
        $driver->setConfiguration(new Configuration([
            'database' => $database,
            'driver' => 'mysql',
            'host' => $hostname,
            'password' => $password,
            'username' => $username,
        ]));

        return $driver;
    }

    protected static function createPDOPgSQLDriver(): PDODriver
    {
        if ($hostname = \getenv('PGSQL_HOSTNAME')) {
            $database = \getenv('PGSQL_DATABASE');
            $username = \getenv('PGSQL_PASSWORD');
            $password = \getenv('PGSQL_USERNAME');
        } else {
            self::markTestSkipped(\sprintf("'PGSQL_HOSTNAME' environment variable is missing."));
        }

        $driver = new PDODriver();
        $driver->setConfiguration(new Configuration([
            'database' => $database,
            'driver' => 'pgsql',
            'host' => $hostname,
            'password' => $password,
            'username' => $username,
        ]));

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

    public function getRunner(): Runner
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
