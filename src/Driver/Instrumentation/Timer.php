<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

use Goat\Query\QueryError;

final class Timer
{
    /** @var float */
    private $startsAt;

    /** @var null|float */
    private $stopsAt;

    public function __construct()
    {
        $this->startsAts = \microtime(true);
    }

    /**
     * Stop timer and return total duration
     */
    public function stop(): int
    {
        if (null !== $this->stopsAt) {
            throw new QueryError("You cannot stop a timer twice");
        }

        $this->stopsAt = \microtime(true);

        return $this->getDuration();
    }

    /**
     * Get duration in milliseconds.
     *
     * If timer was not stopped, this will return the current timing.
     */
    public function getDuration(): int
    {
        if (null === $this->stopsAt) {
            return (int)\round((\microtime(true) - $this->startsAt) * 1000);
        }
        return (int)\round(($this->stopsAt - $this->startsAt) * 1000);
    }
}
