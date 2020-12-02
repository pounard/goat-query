<?php

declare(strict_types=1);

namespace Goat\Runner;

/**
 * SQL single session runtime configuration.
 *
 * All options here are runtime options. For now, there's not much, but it will
 * continue to grow following converter needs, mostly.
 *
 * For convenience, it may contain arbitrary options in an array.
 */
final class SessionConfiguration
{
    private string $clientEncoding; 
    private string $clientTimeZone;
    private string $database;
    private string $driver;
    /** @var array<string,string> */
    private array $options = [];

    public function __construct(
        string $clientEncoding,
        string $clientTimeZone,
        string $database,
        string $driver,
        array $options
    ) {
        $this->clientEncoding = $clientEncoding;
        $this->clientTimeZone = $clientTimeZone;
        $this->database = $database;
        $this->driver = $driver;
        $this->options = $options;
    }

    public static function empty(): self
    {
        return new self('UTF-8', "UTC", 'null', 'null', []);
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getClientEncoding(): string
    {
        return $this->clientEncoding;
    }

    public function getClientTimeZone(): string
    {
        return $this->clientTimeZone;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function get(string $name): ?string
    {
        return $this->options[$name] ?? null;
    }
}
