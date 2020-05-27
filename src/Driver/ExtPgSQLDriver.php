<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Driver\Platform\PgSQLPlatform;
use Goat\Driver\Platform\Platform;
use Goat\Driver\Platform\Escaper\Escaper;
use Goat\Driver\Platform\Escaper\ExtPgSQLEscaper;
use Goat\Driver\Runner\ExtPgSQLRunner;
use Goat\Runner\Runner;

class ExtPgSQLDriver extends AbstractDriver
{
    /** @var null|resource */
    private $connection = null;
    private ?Escaper $escaper = null;

    /**
     * {@inheritdoc}
     */
    protected function isConnected(): bool
    {
        return \is_resource($this->connection);
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
    public function canBeClosedProperly(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doConnect(): void
    {
        $configuration = $this->getConfiguration();
        $connectionString = $this->buildConnectionString($configuration->getOptions());

        try {
            $this->connection = $resource = \pg_connect($connectionString, PGSQL_CONNECT_FORCE_NEW);

            \pg_set_error_verbosity($resource,  PGSQL_ERRORS_VERBOSE);
            \pg_query($resource, "SET client_encoding TO ".\pg_escape_literal($configuration->getClientEncoding()));

            $this->escaper = new ExtPgSQLEscaper($this, $this->connection);
            $this->platform = new PgSQLPlatform($this->escaper, $this->getServerVersion());

            $runner = new ExtPgSQLRunner($this->platform, $resource);
            $runner->setLogger($configuration->getLogger());
            $this->runner = $runner;

            /*
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
    protected function doLookupServerVersion(): ?string
    {
        if (!$this->connection) {
            throw new ConfigurationError("Server connection is closed.");
        }

        // @todo error handling here?
        return \pg_version($this->connection)['server'];
    }

    /**
     * {@inheritdoc}
     *
    protected function fetchDatabaseInfo() : array
    {
        $conn = $this->getConn();
        $resource = @\pg_query($conn, "select version();");

        if (false === $resource) {
            $this->serverError($conn);
        }

        $row = @\pg_fetch_array($resource);
        if (false === $row) {
            $this->resultError($resource);
        }

        // Example string to parse:
        //   PostgreSQL 9.2.9 on x86_64-unknown-linux-gnu, compiled by gcc (GCC) 4.4.7 20120313 (Red Hat 4.4.7-4), 64-bit
        $string = \reset($row);
        $pieces = \explode(', ', $string);
        $server = \explode(' ', $pieces[0]);

        return [
            'name'    => $server[0],
            'version' => $server[1],
            'arch'    => $pieces[2],
            'os'      => $server[3],
            'build'   => $pieces[1],
        ];
    }
     */

    /**
     * {@inheritdoc}
     *
    public function setClientEncoding(string $encoding)
    {
        // https://www.postgresql.org/docs/9.3/static/multibyte.html#AEN34087
        // @todo investigate differences between versions

        throw new NotImplementedError();

        // @todo this cannot work
        $this
            ->getConn()
            ->query(
                \sprintf(
                    "SET CLIENT_ENCODING TO %s",
                    $this->getEscaper()->escapeLiteral($encoding)
                )
            )
        ;
    }
     */

    /**
     * Send PDO configuration
     *
    protected function sendConfiguration(array $configuration)
    {
        $pdo = $this->getConn();

        foreach ($configuration as $key => $value) {
            $pdo->query(\sprintf(
                "SET %s TO %s",
                $this->getEscaper()->escapeIdentifier($key),
                $this->getEscaper()->escapeLiteral($value)
            ));
        }

        return $this;
    }
     */

    /**
     * {@inheritdoc}
     */
    protected function doClose(): void
    {
        if (\is_resource($this->connection)) {
            \pg_close($this->connection);
            $this->connection = null;
        }
        $this->platform = null;
        $this->runner = null;
        // Without \gc_collect_cycles() call, unit tests will fail.
        \gc_collect_cycles();
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreatePlatform(): Platform
    {
        if (!$this->connection) {
            $this->connect();
        }
        return $this->platform;
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateRunner(): Runner
    {
        if (!$this->connection) {
            $this->connect();
        }
        return $this->runner;
    }
}
