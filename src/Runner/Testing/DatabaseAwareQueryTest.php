<?php

namespace Goat\Runner\Testing;

use Goat\Hydrator\HydratorMap;
use Goat\Runner\Runner;
use Goat\Runner\Driver\AbstractRunner;
use Goat\Runner\Driver\PDOMySQLRunner;
use Goat\Runner\Driver\PDOPgSQLRunner;
use PHPUnit\Framework\TestCase;

abstract class DatabaseAwareQueryTest extends TestCase
{
    /**
     * Data provider for most functions, first parameter is a Runner object,
     * second parameter is a $supportsReturning boolean.
     */
    public function getRunners(): iterable
    {
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

        if (\getenv('ENABLE_PDO')) {
            if ($mysqlHost) {
                $connection = new \PDO(\sprintf('mysql:host=%s;dbname=%s', $mysqlHost, $mysqlBase), $mysqlUser, $mysqlPass);
                $connection->query("SET character_set_client = 'UTF-8'");
                $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                yield [new PDOMySQLRunner($connection), false];
            }
            if ($pgsqlHost) {
                $connection = new \PDO(\sprintf('pgsql:host=%s;dbname=%s', $pgsqlHost, $pgsqlBase), $pgsqlUser, $pgsqlPass);
                $connection->query("SET character_set_client = 'UTF-8'");
                $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                yield [new PDOPgSQLRunner($connection), false];
            }
        }

        /*
        if ($pgsqlHost && getenv('ENABLE_EXT_PGSQL')) {
            $dsn = \sprintf("host=%s port=%s dbname=%s user=%s password=%s", $pgsqlHost, 5432, $pgsqlBase, $pgsqlUser, $pgsqlPass);
            $resource = \pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
            yield new Extpg
        }
         */
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
     * Sorry, but you must call this method manually as of now.
     */
    protected function prepare(Runner $runner)
    {
        if ($runner instanceof AbstractRunner && \class_exists(HydratorMap::class)) {
            $runner->setHydratorMap(new HydratorMap(\sys_get_temp_dir().'/'.\uniqid('test-')));
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
