<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Runner\Runner;
use Goat\Runner\Driver\ExtPgSQLRunner;

class ExtPgSQLDriver implements Driver
{
    private $configuration;
    private $connection;
    private $runner;

    /**
     * Is connection alive
     */
    private function isConnected(): bool
    {
        return \is_resource($this->connection);
    }

    /**
     * Create runner
     */
    private function createRunner(): Runner
    {
        if (!$this->connection) {
            $this->connect();
        }

        return new ExtPgSQLRunner($this->connection);
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
     * Get configuration
     */
    public function getConfiguration(): Configuration
    {
        if (!$this->configuration) {
            throw new ConfigurationError("No configuration was set");
        }
        return $this->configuration;
    }

    /**
     * Run connection
     *
     * This method might actually never be called, the driver/runner combo
     * can handle it by itself and do lazy-initialization.
     */
    public function connect(): void
    {
        $configuration = $this->getConfiguration();
        $connectionString = $configuration->toExtPgSQLConnectionString();

        // $dsn = \sprintf("host=%s port=%s dbname=%s user=%s password=%s", $pgsqlHost, 5432, $pgsqlBase, $pgsqlUser, $pgsqlPass);
        // $resource = \pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);

        try {
            $connection = \pg_connect($connectionString, PGSQL_CONNECT_FORCE_NEW);

            /*
            $connection->query(\sprintf(
                "SET character_set_client = %s",
                $connection->quote($configuration->getClientEncoding())
            ));

            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            foreach ($configuration->getDriverOptions() as $attribute => $value) {
                $connection->setAttribute($attribute, $value);
            }
             */

        } finally {
            /*
            if (\is_resource($connection)) {
                \pg_close($connection);
            }
             */
        }

        $this->connection = $connection;
    }

    /**
     * Close connection
     *
     * This method might be honnored, even if connect() was not called and
     * connection was lazy-initialized.
     */
    public function close(): void
    {
        if (\is_resource($this->connection)) {
            $this->connection = null;
        }
        $this->runner = null; // @todo Not sure we should do this.
    }

    /**
     * Get runner
     */
    public function getRunner(): Runner
    {
        return $this->runner ?? ($this->runner = $this->createRunner());
    }
}
