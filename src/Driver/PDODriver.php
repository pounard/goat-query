<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Driver\Platform\MySQLPlatform;
use Goat\Driver\Platform\PgSQLPlatform;
use Goat\Driver\Platform\Platform;
use Goat\Driver\Platform\Escaper\PDOMySQLEscaper;
use Goat\Driver\Platform\Escaper\PDOPgSQLEscaper;
use Goat\Driver\Runner\PDOMySQLRunner;
use Goat\Driver\Runner\PDOPgSQLRunner;
use Goat\Runner\Runner;
use Goat\Runner\SessionConfiguration;

class PDODriver extends AbstractDriver
{
    /**
     * Create driver from existing PDO connection
     */
    public static function createFromPDO(\PDO $connection, string $driverName): self
    {
        $configuration = new Configuration([
            'driver' => $driverName,
        ]);

        $ret = new self();
        $ret->setConfiguration($configuration);
        $ret->connection = $connection;

        $ret->preparePlatform();

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeClosedProperly(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doConnect(SessionConfiguration $sessionConfiguration)
    {
        $configuration = $this->getConfiguration();
        $connectionString = $this->buildConnectionString($configuration->getOptions());

        // @todo set the client encoding properly.
        // $clientEncoding = $sessionConfiguration->getClientEncoding();
        $clientTimeZone = $sessionConfiguration->getClientTimeZone();

        $connection = new \PDO(
            $connectionString,
            $configuration->getUsername(),
            $configuration->getPassword()
        );

        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        switch ($driver = $configuration->getDriver()) {

            case 'mysql':
                // @todo Warning: escaping.
                $connection->query("SET time_zone = " . $connection->quote($clientTimeZone));
                break;

            case 'pgsql':
                // @todo Warning: escaping.
                $connection->query("SET TIME ZONE " . $connection->quote($clientTimeZone));
                break;

            default:
                throw new ConfigurationError(\sprintf("Cannot create runner for driver '%s'", $driver));
        }

        /*
         * @todo?
         *
        foreach ($configuration->getDriverOptions() as $attribute => $value) {
            $connection->setAttribute($attribute, $value);
        }
         */

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConnected(/* mixed */ $connectionResource): bool
    {
        if (!$connectionResource instanceof \PDO) {
            throw new \InvalidArgumentException("Driver is broken, connection should be a \\PDO instance.");
        }

        // There is no way to programatically close a PDO connection so we are
        // just going to let the driver set the connection to null and let PDO
        // close gracefuly when being cleaned up by garbage collector. But in
        // real life, it just doesn't work and connection never closes.
    }

    /**
     * {@inheritdoc}
     */
    protected function doClose(/* mixed */ $connectionResource): void
    {
        if (!$connectionResource instanceof \PDO) {
            throw new \InvalidArgumentException("Driver is broken, connection should be a \\PDO instance.");
        }
        \assert($connectionResource instanceof \PDO);
    }

    /**
     * {@inheritdoc}
     */
    protected function doLookupServerVersion(/* mixed */ $connectionResource): ?string
    {
        if (!$connectionResource instanceof \PDO) {
            throw new \InvalidArgumentException("Driver is broken, connection should be a \\PDO instance.");
        }
        \assert($connectionResource instanceof \PDO);

        return $connectionResource->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateRunner(SessionConfiguration $sessionConfiguration, Configuration $configuration): Runner
    {
        switch ($driver = $sessionConfiguration->getDriver()) {

            case 'mysql':
                $runner = new PDOMySQLRunner($this, $sessionConfiguration);
                $runner->setLogger($configuration->getLogger());

                return $runner;

            case 'pgsql':
                $runner = new PDOPgSQLRunner($this, $sessionConfiguration);
                $runner->setLogger($configuration->getLogger());

                return $runner;

            default:
                throw new ConfigurationError(\sprintf("Cannot create runner for driver '%s'", $driver));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreatePlatform($connectionResource, string $serverVersion): Platform
    {
        if (!$connectionResource instanceof \PDO) {
            throw new \InvalidArgumentException("Driver is broken, connection should be a \\PDO instance.");
        }

        switch ($driver = $this->getConfiguration()->getDriver()) {

            case 'mysql':
                return new MySQLPlatform(new PDOMySQLEscaper($connectionResource), $serverVersion);

            case 'pgsql':
                return new PgSQLPlatform(new PDOPgSQLEscaper($connectionResource), $serverVersion);

            default:
                throw new ConfigurationError(\sprintf("Cannot create runner for driver '%s'", $driver));
        }
    }

    /**
     * Creates a valid PDO connection string
     */
    private function buildConnectionString(array $options): string
    {
        $params = [
            'port' => $options['port'],
            'dbname' => $options['database'],
        ];

        // @todo this should be the connection object responsability to set the
        //   client options, because they may differ from versions to versions
        //   even using the same driver
        switch ($driver = $options['driver']) {

            case 'mysql':
                $params['charset'] = $options['charset'];
                break;

            case 'pgsql':
                $params['client_encoding'] = $options['charset'];
                break;

            default:
                throw new ConfigurationError(\sprintf("'%s': unsupported driver", $driver));
        }

        if ($options['socket']) {
            $dsn = $driver . ':unix_socket='.$options['socket'];
        } else {
            $dsn = $driver . ':host='.$options['host'] ?? 'localhost';
        }

        foreach ($params as $key => $value) {
            if ($value) {
                $dsn .= ';'.$key.'='.$value;
            }
        }

        return $dsn;
    }
}
