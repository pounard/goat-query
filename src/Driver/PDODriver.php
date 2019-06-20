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
        $connectionString = $configuration->toPDOConnectionString();

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
     * Close connection
     *
     * This method might be honnored, even if connect() was not called and
     * connection was lazy-initialized.
     */
    public function close(): void
    {
        if ($this->connection) {
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
