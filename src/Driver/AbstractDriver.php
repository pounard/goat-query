<?php

declare(strict_types=1);

namespace Goat\Driver;

abstract class AbstractDriver implements Driver
{
    /** @var null|Configuration */
    private $configuration;

    /** @var bool */
    private $isClosed = true;

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
        $this->isClosed = true;
        $this->doClose();
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
