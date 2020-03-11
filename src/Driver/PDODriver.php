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
    /** @var null|\PDO */
    private $connection;

    /** @var null|Escaper */
    private $escaper;

    /** @var null|Platform */
    private $platform;

    /** @var null|Runner */
    private $runner;

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
    public function canBeClosedProperly(): bool
    {
        return false;
    }

    /**
     * Create and prepare internals.
     */
    private function preparePlatform(): void
    {
        switch ($driver = $this->getConfiguration()->getDriver()) {

            case 'mysql':
                $this->escaper = new PDOMySQLEscaper($this->connection);
                $this->platform = new MySQLPlatform($this->escaper);
                $this->runner = new PDOMySQLRunner($this->platform, $this->connection);
                break;

            case 'pgsql':
                $this->escaper = new PDOPgSQLEscaper($this->connection);
                $this->platform = new PgSQLPlatform($this->escaper);
                $this->runner = new PDOPgSQLRunner($this->platform, $this->connection);
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

        foreach ($configuration->getDriverOptions() as $attribute => $value) {
            $connection->setAttribute($attribute, $value);
        }

        $this->preparePlatform();
    }

    /**
     * {@inheritdoc}
     */
    protected function doClose(): void
    {
        // Attention here, if something kept a reference to the \PDO object
        // it will not close the connection, you've been warned. This is
        // especially true during unit and functionnal tests. Hence the
        // \gc_collect_cycles() call at the end of this method.
        $this->runner = null;
        $this->platform = null;
        $this->connection = null;
        \gc_collect_cycles();
    }

    /**
     * Create platform
     */
    private function createPlatform(): Platform
    {
        if (!$this->connection) {
            $this->connect();
        }
        return $this->platform;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlatform(): Platform
    {
        return $this->platform ?? $this->createPlatform();
    }

    /**
     * Create runner
     */
    private function createRunner(): Runner
    {
        if (!$this->connection) {
            $this->connect();
        }
        return $this->runner;
    }

    /**
     * {@inheritdoc}
     */
    public function getRunner(): Runner
    {
        return $this->runner ?? $this->createRunner();
    }
}
