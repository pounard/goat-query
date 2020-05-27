<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Driver\Platform\MySQLPlatform;
use Goat\Driver\Platform\PgSQLPlatform;
use Goat\Driver\Platform\Platform;
use Goat\Driver\Platform\Escaper\Escaper;
use Goat\Driver\Platform\Escaper\PDOMySQLEscaper;
use Goat\Driver\Platform\Escaper\PDOPgSQLEscaper;
use Goat\Driver\Runner\PDOMySQLRunner;
use Goat\Driver\Runner\PDOPgSQLRunner;
use Goat\Runner\Runner;

class PDODriver extends AbstractDriver
{
    private ?\PDO $connection = null;
    private ?Escaper $escaper = null;

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
     * Is connection alive
     */
    protected function isConnected(): bool
    {
        return null !== $this->connection;
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

    /**
     * {@inheritdoc}
     */
    public function canBeClosedProperly(): bool
    {
        return false;
    }

    /**
     * Create and prepare internals.
     */
    private function preparePlatform(): void
    {
        $configuration = $this->getConfiguration();
        $serverVersion = $this->getServerVersion();

        switch ($driver = $configuration->getDriver()) {

            case 'mysql':
                $this->escaper = new PDOMySQLEscaper($this->connection);
                $this->platform = new MySQLPlatform($this->escaper, $serverVersion);

                $runner = new PDOMySQLRunner($this->platform, $this->connection);
                $runner->setLogger($configuration->getLogger());
                $this->runner = $runner;
                break;

            case 'pgsql':
                $this->escaper = new PDOPgSQLEscaper($this->connection);
                $this->platform = new PgSQLPlatform($this->escaper, $serverVersion);

                $runner = new PDOPgSQLRunner($this->platform, $this->connection);
                $runner->setLogger($configuration->getLogger());
                $this->runner = $runner;
                break;

            default:
                $this->connection = null;
                throw new ConfigurationError(\sprintf("Cannot create runner for driver '%s'", $driver));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doConnect(): void
    {
        $configuration = $this->getConfiguration();
        $connectionString = $this->buildConnectionString($configuration->getOptions());

        $this->connection = $connection = new \PDO(
            $connectionString,
            $configuration->getUsername(),
            $configuration->getPassword()
        );

        /*
        $connection->query(\sprintf(
            "SET character_set_client = %s",
            $connection->quote($configuration->getClientEncoding())
        ));
         */

        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        /*
        foreach ($configuration->getDriverOptions() as $attribute => $value) {
            $connection->setAttribute($attribute, $value);
        }
         */

        $this->preparePlatform();
    }

    /**
     * {@inheritdoc}
     */
    protected function doLookupServerVersion(): ?string
    {
        if (!$this->connection) {
            throw new ConfigurationError("Server connection is closed.");
        }

        return $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * {@inheritdoc}
     */
    protected function doClose(): void
    {
        $this->runner = null;
        $this->platform = null;
        $this->connection = null;
        // Without \gc_collect_cycles() call, unit tests will fail.
        \gc_collect_cycles();
    }

    /**
     * Create platform
     */
    protected function doCreatePlatform(): Platform
    {
        if (!$this->connection) {
            $this->connect();
        }
        return $this->platform;
    }

    /**
     * Create runner
     */
    protected function doCreateRunner(): Runner
    {
        if (!$this->connection) {
            $this->connect();
        }
        return $this->runner;
    }
}
