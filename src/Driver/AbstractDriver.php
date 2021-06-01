<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Driver\Platform\Platform;
use Goat\Runner\Runner;
use Goat\Runner\SessionConfiguration;

abstract class AbstractDriver implements Driver
{
    private /* null|resource|object $connection */ $connection = null;
    private ?Configuration $configuration = null;
    private bool $isClosed = true;
    private bool $hasBeenInitOnce = false;
    private ?string $serverVersion = null;
    private bool $serverVersionLookupDone = false;
    private bool $createdFromExternalConnexion = false;
    private ?Platform $platform = null;
    private ?Runner $runner = null;

    /**
     * On object destruction, force connection to close.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Initialize internal connection from an external, unhandled connection.
     */
    protected function initializeFromExternalConnection(/* mixed */ $connectionResource, Configuration $configuration): void
    {
        if ($this->hasBeenInitOnce) {
            throw new ConfigurationError("Cannot initialize from an external connection object an already initialized driver.");
        }
        if (!$this->isConnected($connectionResource)) {
            throw new ConfigurationError("Connection resource or object is not connected.");
        }

        $this->createdFromExternalConnexion = true;
        $this->hasBeenInitOnce = true;

        $this->configuration = $configuration;
        $this->connection = $connectionResource;
        $this->isClosed = false;
    }

    /**
     * Really connect the connection.
     *
     * @return mixed
     *   Return the connection object/handle. This method is lazy and will be
     *   called at first SQL query execution attempt. Return type will depend
     *   upon the implementation.
     */
    protected abstract function doConnect(SessionConfiguration $sessionConfiguration);

    /**
     * Is connection valid and alive.
     *
     * You must ensure resource or object is the right type, and that connection
     * is opened as well.
     *
     * @param mixed $connectionResource
     *   Anything that was returned by doConnect().
     */
    protected abstract function isConnected(/* mixed */ $connectionResource): bool;

    /**
     * Really close the connection.
     *
     * @param mixed $connectionResource
     *   Anything that was returned by doConnect().
     */
    protected abstract function doClose(/* mixed */ $connectionResource): void;

    /**
     * Lookup server version, result will be cached for the connection lifetime.
     *
     * @param mixed $connectionResource
     *   Anything that was returned by doConnect().
     */
    protected abstract function doLookupServerVersion(/* mixed */ $connectionResource): ?string;

    /**
     * Create platform.
     *
     * In opposition with doCreateRunner() this method will be called lazyly
     * much further in time, usually when first SQL query gets executed, in this
     * method, you can proceed with SQL query execution.
     *
     * In theory, it will never be pending a transaction since that transactions
     * need the platform to be created previously to handle them.
     *
     * @param mixed $connectionResource
     *   Anything that was returned by doConnect().
     */
    protected abstract function doCreatePlatform(/* mixed */ $connectionResource, string $serverVersion): Platform;

    /**
     * Create runner.
     *
     * This method will be called, the connection will NOT be opened, beware
     * that you MUST NOT execute SQL at runner creation time, or the lazy
     * initialization will not be lazy anymore, causing potential performance
     * issues in some applications.
     */
    protected abstract function doCreateRunner(SessionConfiguration $sessionConfiguration, Configuration $configuration): Runner;

    /**
     * Create session configuration.
     */
    protected function createSessionConfiguration(): SessionConfiguration
    {
        $configuration = $this->getConfiguration();

        return new SessionConfiguration(
            $configuration->getClientEncoding(),
            $configuration->getClientTimeZone(),
            $configuration->getDatabase(),
            $configuration->getDriver(),
            []
        );
    }

    /**
     * {@inheritdoc}
     */
    final public function connect()
    {
        if ($this->connection) {
            return $this->connection;
        }
        if ($this->createdFromExternalConnexion) {
            throw new ConfigurationError("Driver was initialized using an external connection which has been closed.");
        }

        $this->hasBeenInitOnce = true;

        $configuration = $this->getConfiguration();
        $configuration->getLogger()->info(\sprintf("[goat-query] Connecting to %d using %s.", $configuration->toString(), static::class));

        $resource = $this->doConnect($this->createSessionConfiguration());

        if (!$resource || (!\is_resource($resource) && !\is_object($resource))) {
            throw new \LogicException("Connection is neither a valid resource nor an object.");
        }

        $this->isClosed = false;

        return $this->connection = $resource;
    }

    /**
     * Get server version.
     */
    final public function getServerVersion(): ?string
    {
        // Server version may have been forced by configuration, in this case
        // we need to return this one, in case serverVersion is still null or
        // empty, maybe we could not find it, therefore return the empty
        // value here as well to avoid multiple roundtrips to the server.
        if ($this->serverVersionLookupDone || $this->serverVersion) {
            return $this->serverVersion;
        }

        return $this->serverVersion = $this->doLookupServerVersion($this->connect());
    }

    /**
     * {@inheritdoc}
     */
    final public function close(): void
    {
        $configuration = $this->getConfiguration();

        if ($this->createdFromExternalConnexion) {
            $configuration->getLogger()->warning(\sprintf("[goat-query] Driver attempted to close an external connection resource using %s, ignoring.", static::class));

            return;
        }

        if (!$this->connection) {
            $configuration->getLogger()->info(\sprintf("[goat-query] Attempting disconnection on an already closed resource using %s, ignoring.", static::class));

            return;
        }

        $configuration->getLogger()->info(\sprintf("[goat-query] Disconnecting from %s using %s.", $configuration->toString(), static::class));

        try {
            $this->doClose($this->connection);
        } finally {
            $this->connection = null;
            $this->isClosed = true;
            $this->platform = null;
            $this->runner = null;

            // Without \gc_collect_cycles() call, unit tests will fail.
            \gc_collect_cycles();
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function getPlatform(): Platform
    {
        return $this->platform ?? (
            $this->platform = $this->doCreatePlatform(
                $this->connect(),
                $this->getServerVersion()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    final public function getRunner(): Runner
    {
        return $this->runner ?? (
            $this->runner = $this->doCreateRunner(
                $this->createSessionConfiguration(),
                $this->getConfiguration()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(Configuration $configuration): void
    {
        if ($this->connection) {
            throw new ConfigurationError("Cannot set configuration after connection has been made");
        }
        $this->configuration = $configuration;

        if ($serverVersion = $configuration->getServerVersion()) {
            $this->serverVersion = $serverVersion;
        }
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
}
