<?php

declare(strict_types=1);

namespace Goat\Runner\Testing;

use Goat\Driver\Configuration;
use Goat\Driver\Driver;
use Goat\Driver\Platform\Platform;
use Goat\Runner\Runner;
use Goat\Runner\SessionConfiguration;

class NullDriver implements Driver
{
    /**
     * {@inheritdoc}
     */
    public function setConfiguration(Configuration $configuration): void
    {
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
    public function connect()
    {
        return new \stdClass();
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getPlatform(): Platform
    {
        return new NullPlatform();
    }

    /**
     * {@inheritdoc}
     */
    public function getRunner(): Runner
    {
        return new NullRunner($this, SessionConfiguration::empty());
    }
}
