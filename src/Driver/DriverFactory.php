<?php

declare(strict_types=1);

namespace Goat\Driver;

use Doctrine\DBAL\Connection;

final class DriverFactory
{
    /**
     * Create driver from existing PDO connection
     */
    public static function fromPDOConnection(\PDO $connection, string $driverName): Driver
    {
        return PDODriver::fromPDOConnection($connection, $driverName);
    }

    /**
     * Create runner instance from Doctrine connection
     *
     * @throws \InvalidArgumentException
     *   If the underlaying doctrine platform is unsupported
     */
    public static function fromDoctrineConnection(Connection $connection): Driver
    {
        $realConnection = $connection->getWrappedConnection();
        if (!$realConnection instanceof \PDO) {
            throw new \InvalidArgumentException("Doctrine connection does not use PDO, goat database runner cannot work on top of it");
        }

        $ret = null;

        switch ($platformName = $connection->getDatabasePlatform()->getName()) {

            case 'postgresql':
                $ret = PDODriver::createFromPDO($realConnection, 'pgsql');
                break;

            case 'mysql':
                $ret = PDODriver::createFromPDO($realConnection, 'mysql');
                break;

            default:
                throw new \InvalidArgumentException(\sprintf(
                    "'%s' Doctrine platform is unsupported, only 'postgresql' and 'mysql' are supported",
                    $platformName
                ));
        }

        return $ret;
    }

    /**
     * Create connection from configuration.
     */
    public static function fromConfiguration(Configuration $configuration): Driver
    {
        switch ($configuration->getDriver()) {

            case Configuration::DRIVER_DEFAULT_MYSQL:
                $driver = new PDODriver();
                break;

            case Configuration::DRIVER_DEFAULT_PGSQL:
                if (\function_exists('pg_connect')) {
                    $driver = new ExtPgSQLDriver();
                } else {
                    $driver = new PDODriver();
                }
                break;

            case Configuration::DRIVER_EXT_PGSQL:
                $driver = new ExtPgSQLDriver();
                break;

            case Configuration::DRIVER_PDO_MYSQL:
                $driver = new PDODriver();
                break;

            case Configuration::DRIVER_PDO_PGSQL:
                $driver = new PDODriver();
                break;
        }

        $driver->setConfiguration($configuration);

        return $driver;
    }

    /**
     * Create connection from URI/URL/DSN.
     */
    public static function fromUri(string $uri): Driver
    {
        return self::fromConfiguration(Configuration::fromString($uri));
    }
}
