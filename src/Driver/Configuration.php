<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Query\QueryError;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Runner/driver onfiguration
 */
final class Configuration
{
    const DEFAULT_CHARSET = 'UTF8';
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT_MYSQL = 3306;
    const DEFAULT_PORT_PGSQL = 5432;

    /** MySQL with fallback on first enabled driver implementation */
    const DRIVER_DEFAULT_MYSQL = 'mysql';

    /** PgSQL with fallback on first enabled driver implementation */
    const DRIVER_DEFAULT_PGSQL = 'pgsql';

    /** ext-PgSQL driver */
    const DRIVER_EXT_PGSQL = 'ext-pgsql';

    /** PDO MySQL driver */
    const DRIVER_PDO_MYSQL = 'pdo-mysql';

    /** PDO PgSQL driver */
    const DRIVER_PDO_PGSQL = 'pdo-pgsql';

    /** Default allowed options */
    const ALLOWED_OPTIONS = [
        'charset' => self::DEFAULT_CHARSET,
        'database' => null,
        'driver' => null,
        'host' => self::DEFAULT_HOST,
        'password' => null,
        'port' => null,
        'socket' => null,
        'username' => null,
    ];

    /** @deprecated @todo fix this */
    const REGEX_UNIX = '@^(unix\://|)([\w]+)\://(/[^\:]+)\:(.+)$@';

    /** @var array<string,bool|int|float|string> */
    private $options = [];

    /** @var array<string,bool|int|float|string> */
    private $driverOptions = [];

    /** @var null|LoggerInterface */
    private $logger;

    /** @var null|string */
    private $description;

    /**
     * Default constructor
     */
    public function __construct(array $options, array $driverOptions = [])
    {
        if (empty($options['driver'])) {
            throw new QueryError("Options must contain the 'driver' value");
        }

        // Reduce options to allowed ones only.
        $this->options = \array_replace(self::ALLOWED_OPTIONS, $options);
        if (!$this->options['port']) {
            $this->options['port'] = false !== \strpos($options['driver'], 'pg') ? self::DEFAULT_PORT_PGSQL: self::DEFAULT_PORT_MYSQL;
        }

        // Merge disallowed options into driver options.
        foreach (\array_diff_key($options, self::ALLOWED_OPTIONS) as $key => $value) {
            if (\array_key_exists($key, $driverOptions)) {
                throw new \InvalidArgumentException(\sprintf("'%s' key is duplicated in both \$options and \$driverOptions", $key));
            }
            $driverOptions[$key] = $value;
        }
        $this->driverOptions = $driverOptions;
    }

    /**
     * Get a meaningful technical description of this configuration.
     */
    public function toString(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $prefix = $this->options['driver'].'://';

        $uri = '';
        if ($host = ($this->options['host'] ?? null)) {
            if ($port = ($this->options['port'] ?? null)) {
                $uri = $host.':'.$port;
            } else {
                $uri = $host;
            }
        }

        if ($user = ($this->options['username'] ?? null)) {
            return $this->description = $prefix.$user.'@'.$uri;
        }
        return $this->description = $prefix.$uri;
    }

    /**
     * Set logger for driver and runner.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Get logger for driver and runner.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger ?? ($this->logger = new NullLogger());
    }

    /**
     * Parse an arbitrary string value and return a PHP typed one.
     *
     * @return bool|int|float|string
     */
    private static function parseValue(string $value)
    {
        $lowered = \strtolower($value);
        if ("false" === $lowered) {
            return false;
        }
        if ("true" === $lowered) {
            return false;
        }
        $matches = [];
        if (\preg_match('/^\d+(\.\d+|)$/', $value, $matches)) {
            return $matches[1] ? ((float)$value) : ((int)$value);
        }
        return $value;
    }

    /**
     * Normalize host DNS string.
     *
     * @internal This is public for unit testing purpose only
     *
     * @return array<string,bool|int|float|string>
     */
    public static function parseHostString(string $host): array
    {
        $result = \parse_url($host);
        $ret = [
            // Remove leading '/' on database name.
            'database' => isset($result['path']) ? \substr($result['path'], 1) : null,
            'driver' => $result['scheme'] ?? null,
            'host' => $result['host'] ?? null,
            'options' => [],
            'password' => $result['pass'] ?? null,
            'port' => $result['port'] ?? null,
            'username' => $result['user'] ?? null,
        ];
        if (!empty($result['query'])) {
            \parse_str($result['query'] ?? '', $ret['options']);
            foreach ($ret['options'] as $key => $value) {
                if (isset($ret[$key])) {
                    throw new \InvalidArgumentException(\sprintf(
                        "'%s' cannot be speficied in database URI query string",
                        $key
                    ));
                }
                // Basic converstion, "false" = false, "true" = true and numeric
                // values are converted to their PHP rightful types (int or float).
                $ret[$key] = self::parseValue($value);
            }
        }
        return $ret;
    }

    /**
     * Normalize host from arbitrary value.
     *
     * @internal This is public for unit testing purpose only
     *
     * @return array<string,bool|int|float|string>
     */
    public static function normalizeHost($host): array
    {
        if (\is_array($host)) {
            return $host;
        }
        if (\is_string($host)) {
            return self::parseHostString($host);
        }
        throw new \InvalidArgumentException("Host must be an array or a string");
    }

    /**
     * Create instance from string
     *
     * @param array<string,bool|int|float|string> $options
     *   Additional options, they will override ones parsed in URI
     * @param array<string,bool|int|float|string> $driverOptions
     *   Additional driver options, they will override ones parsed in URI
     */
    public static function fromString(string $string, array $options = [], array $driverOptions = []): self
    {
        return new self(self::parseHostString($string), $driverOptions);
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
}
