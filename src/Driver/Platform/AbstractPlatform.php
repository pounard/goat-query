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

    public function __construct(Escaper $escaper)
    {
        $this->escaper = $escaper;
        $this->writer = $this->createSqlWriter($escaper);
    }

    /**
     * Create a new SQL writer instance
     */
    abstract protected function createSqlWriter(Escaper $escaper): SqlWriter;

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
