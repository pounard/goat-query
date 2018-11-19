<?php

namespace Goat\Runner\Tests;

use Goat\Query\ExpressionRaw;
use Goat\Runner\Runner;
use Goat\Runner\Driver\PDOMySQLRunner;
use Goat\Runner\Driver\PDOPgSQLRunner;
use PHPUnit\Framework\TestCase;

class SimpleQueryTest extends TestCase
{
    /**
     * Data provider for most functions, first parameter is a Runner object,
     * second parameter is a $supportsReturning boolean.
     */
    public function getRunners(): iterable
    {
        if ($mysqlHost = getenv('MYSQL_HOSTNAME')) {
            $mysqlBase = getenv('MYSQL_DATABASE');
            $mysqlUser = getenv('MYSQL_USERNAME');
            $mysqlPass = getenv('MYSQL_PASSWORD');
        }
        if ($pgsqlHost = getenv('PGSQL_HOSTNAME')) {
            $pgsqlBase = getenv('PGSQL_DATABASE');
            $pgsqlUser = getenv('PGSQL_PASSWORD');
            $pgsqlPass = getenv('PGSQL_USERNAME');
        }

        if (\getenv('ENABLE_PDO')) {
            if ($mysqlHost) {
                $connection = new \PDO(sprintf('mysql:host=%s;dbname=%s', $mysqlHost, $mysqlBase), $mysqlUser, $mysqlPass);
                $connection->query("SET character_set_client = 'UTF-8'");
                yield [new PDOMySQLRunner($connection), false];
            }
            if ($pgsqlHost) {
                $connection = new \PDO(sprintf('pgsql:host=%s;dbname=%s', $pgsqlHost, $pgsqlBase), $pgsqlUser, $pgsqlPass);
                $connection->query("SET character_set_client = 'UTF-8'");
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
     * @dataProvider getRunners
     */
    public function testSelectOne(Runner $runner, bool $supportsReturning)
    {
        $this->assertSame(13, $runner->execute("SELECT 13")->fetchField());
    }

    /**
     * @dataProvider getRunners
     */
    public function testSelectOneAsQuery(Runner $runner, bool $supportsReturning)
    {
        $this->assertSame(
            42,
            $runner
                ->getQueryBuilder()
                ->select()
                ->columnExpression(ExpressionRaw::create('42'))
                ->execute()
                ->fetchField()
        );
    }

    /**
     * @dataProvider getRunners
     */
    public function testPerformOne(Runner $runner, bool $supportsReturning)
    {
        $this->assertSame(1, $runner->perform("SELECT 1"));
    }
}
