<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Driver\Platform\Platform;
use Goat\Runner\Runner;

abstract class AbstractDriver implements Driver
{
    /** @var null|Configuration */
    private $configuration;

    /** @var bool */
    private $isClosed = true;

    /** @var null|Platform */
    protected $platform;

    /** @var null|Runner */
    protected $runner;

    /**
     * Is connection alive
     */
    abstract protected function isConnected(): bool;

    /**
     * On object destruction, force connection to close.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Really close the connection.
     */
    protected abstract function doConnect(): void;

    /**
     * {@inheritdoc}
     */
    final public function connect(): void
    {
        $configuration = $this->getConfiguration();
        $configuration->getLogger()->info(\sprintf("[goat-query] Connecting to %d using %s.", $configuration->toString(), static::class));

        $this->doConnect();
        $this->isClosed = false;
    }

    /**
     * Really close the connection.
     */
    protected abstract function doClose(): void;

    /**
     * {@inheritdoc}
     */
    final public function close(): void
    {
        $configuration = $this->getConfiguration();
        $configuration->getLogger()->info(\sprintf("[goat-query] Disconnecting from %s using %s.", $configuration->toString(), static::class));

        $this->isClosed = true;
        $this->doClose();
    }

    /**
     * Create platform.
     */
    protected abstract function doCreatePlatform(): Platform;

    /**
     * {@inheritdoc}
     */
    final public function getPlatform(): Platform
    {
        return $this->platform ?? ($this->platform = $this->doCreatePlatform());
    }

    /**
     * Create runner.
     */
    protected abstract function doCreateRunner(): Runner;

    /**
     * {@inheritdoc}
     */
    final public function getRunner(): Runner
    {
        return $this->runner ?? ($this->runner = $this->doCreateRunner());
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
}
