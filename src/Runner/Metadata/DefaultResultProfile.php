<?php

declare(strict_types=1);

namespace Goat\Runner\Metadata;

use Goat\Driver\Instrumentation\Timer;

/**
 * When you instanciate this class, 
 */
final class DefaultResultProfile implements ResultProfile
{
    /** @var bool */
    private $isError = false;

    /** @var Timer */
    private $timer;

    /** @var int */
    private $preparationTime = -1;

    /** @var int */
    private $executionTime = -1;

    public function __construct()
    {
        $this->timer = new Timer();
    }

    public function donePrepare(): void
    {
        $this->preparationTime = $this->timer->getDuration();
    }

    public function doneExecute(): void
    {
        $this->executionTime = $this->timer->stop();
    }

    /**
     * Get preparation time, in milliseconds.
     */
    public function getPreparationTime(): int
    {
        return $this->preparationTime;
    }

    /**
     * Get execution time, in milliseconds.
     */
    public function getExecutionTime(): int
    {
        return $this->executionTime;
    }

    /**
     * Get total time, in milliseconds.
     */
    public function getTotalTime(): int
    {
        return $this->preparationTime + $this->executionTime;
    }

    /**
     * {@inheritdoc}
     */
    public function isComplete(): bool
    {
        return -1 !== $this->preparationTime && -1 !== $this->executionTime;
    }

    /**
     * {@inheritdoc}
     */
    public function isError(): bool
    {
        return $this->isError;
    }
}
