<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

use Goat\Query\QueryError;

final class Timer implements ProfilerResult
{
    private float $startsAt;
    private ?float $stopsAt = null;

    /**
     * Start timer.
     */
    public function __construct()
    {
        $this->startsAt = \microtime(true);
    }

    /**
     * Stop timer and return total duration.
     */
    public function stop(): int
    {
        if (null !== $this->stopsAt) {
            throw new QueryError("You cannot stop a timer twice.");
        }

        $this->stopsAt = \microtime(true);

        return $this->getTotalTime();
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalTime(): int
    {
        if (null === $this->stopsAt) {
            return (int) \round((\microtime(true) - $this->startsAt) * 1000);
        }
        return (int) \round(($this->stopsAt - $this->startsAt) * 1000);
    }

    /**
     * {@inheritdoc}
     */
    public function isError(): bool
    {
        return false;
    }
}
