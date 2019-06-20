<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Query\QueryError;

/**
 * Runner/driver onfiguration
 */
final class Configuration
{
    const DEFAULT_CHARSET = 'UTF8';
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT_MYSQL = 3306;
    const DEFAULT_PORT_PGSQL = 5432;

    // @todo missing username and password
    const REGEX_UNIX = '@^(unix\://|)([\w]+)\://(/[^\:]+)\:(.+)$@';
    const REGEX_URL = '@^([\w]+)\://(([^/\:]+)(\:(\d+)|)|)/([^\.]+)$@';

    private $options = [];
    private $driverOptions = [];

    /**
     * Default constructor
     */
    public function __construct(array $options, array $driverOptions = [])
    {
        if (empty($options['driver'])) {
            throw new QueryError("Options must contain the 'driver' value");
        }

        $this->options = $options + [
            'charset' => self::DEFAULT_CHARSET,
            'database' => null,
            'driver' => null,
            'host' => self::DEFAULT_HOST,
            'password' => null,
            'port' => false !== \strpos($options['driver'], 'pg') ? self::DEFAULT_PORT_PGSQL: self::DEFAULT_PORT_MYSQL,
            'socket' => null,
            'username' => null,
        ];
        $this->driverOptions = $driverOptions;
    }

    /**
     * Create instance from string
     *
     * Allow two different schemes:
     *   - DBTYPE://[USERNAME[:PASSWORD]@][HOSTNAME[:PORT]]/DATABASE
     *   - [UNIX://]DBTYPE:///PATH/TO/SOCKET:DATABASE
     */
    public static function fromString(string $string, array $options = [], array $driverOptions = []): self
    {
        $matches = [];
        if (\preg_match(self::REGEX_TCP, $string, $matches)) {
            $options['driver'] = $matches[2];
            $options['host'] = $matches[4];
            $options['port'] = (int)$matches[6];
            $options['database'] = $matches[7];
        } else if (\preg_match(self::REGEX_UNIX, $string, $matches)) {
            $options['driver'] = $matches[2];
            $options['socket'] = $matches[3];
            $options['database'] = $matches[4];
        }
    }

    /**
     * Get internal driver name, usually a string such as 'mysql' or 'pgsql'.
     */
    public function getDriver(): string
    {
        return $this->options['driver'];
    }

    /**
     * Get username if specified
     */
    public function getUsername(): ?string
    {
        return $this->options['username'];
    }

    /**
     * Get password if specified
     */
    public function getPassword(): ?string
    {
        return $this->options['password'];
    }

    /**
     * Get client encoding (connection charset)
     */
    public function getClientEncoding(): string
    {
        return $this->options['charset'];
    }

    /**
     * Get all options as array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get all driver options as array
     */
    public function getDriverOptions(): array
    {
        return $this->driverOptions;
    }

    /**
     * Creates a valid ext-pgsql connection string
     */
    public function toExtPgSQLConnectionString(): string
    {
        $options = [
            'port' => $this->options['port'],
            'dbname' => $this->options['database'],
            'user' => $this->options['username'],
            'password' => $this->options['password'],
        ];

        // If 'host' is an absolute path, the library will lookup for the
        // socket by itself, no need to specify it.
        $dsn = 'host=' . $this->options['host'];

        foreach ($options as $key => $value) {
            if ($value) {
                $dsn .= ' ' . $key . '=' . $value;
            }
        }

        return $dsn;
    }

    /**
     * Creates a valid PDO connection string
     */
    public function toPDOConnectionString(): string
    {
        $driver = $this->options['driver'];

        $options = [
            'port' => $this->options['port'],
            'dbname' => $this->options['database'],
        ];

        // @todo this should be the connection object responsability to set the
        //   client options, because they may differ from versions to versions
        //   even using the same driver
        switch ($driver) {

            case 'mysql':
                $options['charset'] = $this->options['charset'];
                break;

            case 'pgsql':
                $options['client_encoding'] = $this->options['charset'];
                break;
        }

        if ($this->options['socket']) {
            $dsn = $driver . ':unix_socket=' . $this->options['socket'];
        } else {
            $dsn = $driver . ':host=' . $this->options['host'] ?? 'localhost';
        }

        foreach ($options as $key => $value) {
            if ($value) {
                $dsn .= ';' . $key . '=' . $value;
            }
        }

        return $dsn;
    }
}
