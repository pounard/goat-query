<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Runner\Runner;
use Goat\Runner\Driver\PDOMySQLRunner;
use Goat\Runner\Driver\PDOPgSQLRunner;

class PDODriver implements Driver
{
    private $configuration;
    private $connection;
    private $runner;

    /**
     * Is connection alive
     */
    private function isConnected(): bool
    {
        return null !== $this->connection;
    }

    /**
     * Create runner
     */
    private function createRunner(): Runner
    {
        if (!$this->connection) {
            $this->connect();
        }

        switch ($driver = $this->configuration->getDriver()) {

            case 'mysql':
                return new PDOMySQLRunner($this->connection);

            case 'pgsql':
                return new PDOPgSQLRunner($this->connection);
        }

        throw new ConfigurationError(\sprintf("Cannot create runner for driver '%s'", $driver));
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(Configuration $configuration): void
    {
        if ($this->isConnected()) {
            throw new ConfigurationError("Cannot set configuration after connection has been made");
        }
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(): Configuration
    {
        if (!$this->configuration) {
            throw new ConfigurationError("No configuration was set");
        }
        return $this->configuration;
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
                throw new \InvalidArgumentException(\sprintf("'%s': unsupported driver", $driver));
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
    public function connect(): void
    {
        $configuration = $this->getConfiguration();
        $connectionString = $this->buildConnectionString($configuration->getOptions());

        $connection = new \PDO(
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

        foreach ($configuration->getDriverOptions() as $attribute => $value) {
            $connection->setAttribute($attribute, $value);
        }

        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if ($this->connection) {
            $this->connection = null;
        }
        $this->runner = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRunner(): Runner
    {
        return $this->runner ?? ($this->runner = $this->createRunner());
    }
}
