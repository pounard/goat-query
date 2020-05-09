<?php

declare(strict_types=1);

namespace Goat\Driver\Platform;

use Goat\Driver\Query\SqlWriter;
use Goat\Driver\Platform\Escaper\Escaper;

abstract class AbstractPlatform implements Platform
{
    /** @var Escaper */
    private $escaper;

    /** @var SqlWriter */
    private $writer;

    /** @var null|string */
    private $serverVersion;

    public function __construct(Escaper $escaper, ?string $serverVersion = null)
    {
        $this->escaper = $escaper;
        $this->serverVersion = $serverVersion;
        $this->writer = $this->createSqlWriter($escaper);
    }

    /**
     * Create a new SQL writer instance
     */
    abstract protected function createSqlWriter(Escaper $escaper): SqlWriter;

    /**
     * Get server version.
     */
    final public function getServerVersion(): ?string
    {
        return $this->serverVersion;
    }

    /**
     * {@inheritdoc}
     */
    final public function getEscaper(): Escaper
    {
        return $this->escaper;
    }

    /**
     * {@inheritdoc}
     */
    final public function getSqlWriter(): SqlWriter
    {
        return $this->writer;
    }
}
