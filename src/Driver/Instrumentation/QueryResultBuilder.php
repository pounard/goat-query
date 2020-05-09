<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

final class QueryResultBuilder
{
    private bool $isError = false;
    private int $preparationTime = 0;
    private int $executionTime = 0;
    private Timer $globalTimer;
    private ?Timer $executeTimer;
    private bool $executed = false;

    public function __construct()
    {
        $this->globalTimer = new Timer();
    }

    /**
     * Preparation ended.
     */
    public function prepared(): void
    {
        $this->preparationTime = $this->globalTimer->getTotalTime();
        $this->executeTimer = new Timer();
    }

    /**
     * Execution ended.
     */
    public function executed(): void
    {
        if ($this->executeTimer) {
            $this->executionTime = $this->executeTimer->stop();
            $this->executed = true;
        }
    }

    /**
     * End and return timer.
     */
    public function stop(): QueryResult
    {
        return new QueryResult(
            $this->preparationTime,
            $this->executionTime,
            $this->globalTimer->stop(),
            !$this->executed
        );
    }
}
