<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Driver\Platform\Platform;
use Goat\Runner\Runner;

interface Driver
{
    /**
     * Set configuration.
     *
     * This method will be called once prior to initialization.
     *
     * @throws ConfigurationError
     *   If this method is called subsenquently to connection
     */
    public function setConfiguration(Configuration $configuration): void;

    /**
     * Tell if close() method really closes the connection.
     *
     * PDO is probably the only implementation that won't.
     */
    public function canBeClosedProperly(): bool;

    /**
     * Run connection.
     *
     * This method might actually never be called, the driver/runner combo
     * can handle it by itself and do lazy-initialization.
     */
    public function connect(): void;

    /**
     * Get server version.
     */
    public function getServerVersion(): ?string;

    /**
     * Close connection.
     *
     * This method might be honnored, even if connect() was not called and
     * connection was lazy-initialized.
     */
    public function close(): void;

    /**
     * Get platform.
     */
    public function getPlatform(): Platform;

    /**
     * Get runner.
     */
    public function getRunner(): Runner;
}
