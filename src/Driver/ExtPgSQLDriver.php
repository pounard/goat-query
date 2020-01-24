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
     * Creates a valid ext-pgsql connection string
     */
    private function buildConnectionString(array $options): string
    {
        $params = [
            'port' => $options['port'],
            'dbname' => $options['database'],
            'user' => $options['username'],
            'password' => $options['password'],
        ];

        // If 'host' is an absolute path, the library will lookup for the
        // socket by itself, no need to specify it.
        $dsn = 'host='.$options['host'];

        foreach ($params as $key => $value) {
            if ($value) {
                $dsn .= ' '.$key.'='.$value;
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

        try {
            $this->connection = \pg_connect($connectionString, PGSQL_CONNECT_FORCE_NEW);

            \pg_query($this->connection, "SET client_encoding TO ".\pg_escape_literal($configuration->getClientEncoding()));

            /*
            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            foreach ($configuration->getDriverOptions() as $attribute => $value) {
                $connection->setAttribute($attribute, $value);
            }
             */
        } catch (\Throwable $e) {
            if (\is_resource($this->connection)) {
                \pg_close($this->connection);
            }
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if (\is_resource($this->connection)) {
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
