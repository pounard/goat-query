<?php

namespace Goat\Runner\Testing;

use GeneratedHydrator\Configuration as GeneratedHydratorConfiguration;
use Goat\Converter\DefaultConverter;
use Goat\Driver\Configuration;
use Goat\Driver\ExtPgSQLDriver;
use Goat\Driver\PDODriver;
use Goat\Hydrator\HydratorMap;
use Goat\Runner\Runner;
use Goat\Runner\Driver\AbstractRunner;
use Goat\Runner\Metadata\ApcuResultMetadataCache;
use Goat\Runner\Metadata\ArrayResultMetadataCache;
use PHPUnit\Framework\TestCase;

abstract class DatabaseAwareQueryTest extends TestCase
{
    /**
     * Data provider for most functions, first parameter is a Runner object,
     * second parameter is a $supportsReturning boolean.
     */
    public function getRunners(): iterable
    {
        $apcuEnabled = \function_exists('apcu_add');
        $useApcu = $apcuEnabled && \getenv('ENABLE_APCU');

        if ($mysqlHost = \getenv('MYSQL_HOSTNAME')) {
            $mysqlBase = \getenv('MYSQL_DATABASE');
            $mysqlUser = \getenv('MYSQL_USERNAME');
            $mysqlPass = \getenv('MYSQL_PASSWORD');
        }
        if ($pgsqlHost = \getenv('PGSQL_HOSTNAME')) {
            $pgsqlBase = \getenv('PGSQL_DATABASE');
            $pgsqlUser = \getenv('PGSQL_PASSWORD');
            $pgsqlPass = \getenv('PGSQL_USERNAME');
        }

        $defaultConverter = new DefaultConverter();
        $this->prepareConverter($defaultConverter);

        if (\getenv('ENABLE_PDO')) {
            if ($mysqlHost) {
                $driver = new PDODriver();
                $driver->setConfiguration(new Configuration([
                    'database' => $mysqlBase,
                    'driver' => 'mysql',
                    'host' => $mysqlHost,
                    'password' => $mysqlPass,
                    'username' => $mysqlUser,
                ]));
                $runner = $driver->getRunner();
                $runner->setConverter($defaultConverter);
                $runner->setResultMetadataCache(new ArrayResultMetadataCache());
                yield [$runner, false];

                if ($useApcu) {
                    $driver = new PDODriver();
                    $driver->setConfiguration(new Configuration([
                        'database' => $mysqlBase,
                        'driver' => 'mysql',
                        'host' => $mysqlHost,
                        'password' => $mysqlPass,
                        'username' => $mysqlUser,
                    ]));
                    $runner = $driver->getRunner();
                    $runner->setConverter($defaultConverter);
                    $runner->setResultMetadataCache(new ApcuResultMetadataCache());
                    yield [$runner, false];
                }
            }

            if ($pgsqlHost) {
                $driver = new PDODriver();
                $driver->setConfiguration(new Configuration([
                    'database' => $pgsqlBase,
                    'driver' => 'pgsql',
                    'host' => $pgsqlHost,
                    'password' => $pgsqlPass,
                    'username' => $pgsqlUser,
                ]));
                $runner = $driver->getRunner();
                $runner->setConverter($defaultConverter);
                $runner->setResultMetadataCache(new ArrayResultMetadataCache());
                yield [$runner, false];

                if ($useApcu) {
                    $driver = new PDODriver();
                    $driver->setConfiguration(new Configuration([
                        'database' => $pgsqlBase,
                        'driver' => 'pgsql',
                        'host' => $pgsqlHost,
                        'password' => $pgsqlPass,
                        'username' => $pgsqlUser,
                    ]));
                    $runner = $driver->getRunner();
                    $runner->setConverter($defaultConverter);
                    $runner->setResultMetadataCache(new ApcuResultMetadataCache());
                    yield [$runner, false];
                }
            }
        }

        if ($pgsqlHost && \getenv('ENABLE_EXT_PGSQL')) {
            $driver = new ExtPgSQLDriver();
            $driver->setConfiguration(new Configuration([
                'database' => $pgsqlBase,
                'driver' => 'pgsql',
                'host' => $pgsqlHost,
                'password' => $pgsqlPass,
                'username' => $pgsqlUser,
            ]));
            $runner = $driver->getRunner();
            $runner->setConverter($defaultConverter);
            yield [$runner, false];
        }
    }

    /**
     * @deprecated
     *   This method is there to avoid failure pending tests rewrite.
     */
    public function driverDataSource()
    {
        return [];
    }

    /**
     * Prepare converter
     */
    protected function prepareConverter(DefaultConverter $converter)
    {
        // Register your custom converters if needed
    }

    /**
     * Sorry, but you must call this method manually as of now.
     */
    protected function prepare(Runner $runner)
    {
        if ($runner instanceof AbstractRunner && \class_exists(HydratorMap::class)) {
            $runner->setHydratorMap(new HydratorMap(new GeneratedHydratorConfiguration()));
        }
        $this->createTestSchema($runner);
        $this->createTestData($runner);
    }

    /**
     * Create your test schema.
     *
     * Database will NOT be cleanup up after, you need to do this yourself.
     */
    protected function createTestSchema(Runner $runner)
    {
        // Create your test schema.
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner)
    {
        // Create your test data.
    }
}
