<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

final class QueryResult implements ProfilerResult
{
    private bool $isError = false;
    private int $preparationTime;
    private int $executionTime;
    private int $totalTime;
    // private ?string $rawSQL = null;
    // private ?array $arguments = null;

    public function __construct(int $preparationTime, int $executionTime, int $totalTime, bool $isError = false)
    {
        $this->preparationTime = $preparationTime;
        $this->executionTime = $executionTime;
        $this->totalTime = $totalTime;
    }

    public static function empty(): self
    {
        return new self(0, 0, 0);
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
     * {@inheritdoc}
     */
    public function getTotalTime(): int
    {
        return $this->totalTime;
    }

    /**
     * {@inheritdoc}
     */
    public function isComplete(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isError(): bool
    {
        return $this->isError;
    }
}
