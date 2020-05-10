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
        $this->startsAt = \hrtime(true);
    }

    /**
     * Convert nano seconds to milliseconds and round the result.
     */
    public static function nsecToMsec(float $nsec): int
    {
        return (int) ($nsec / 1e+6);
    }

    /**
     * Stop timer and return total duration.
     */
    public function stop(): int
    {
        if (null !== $this->stopsAt) {
            throw new QueryError("You cannot stop a timer twice.");
        }

        $this->stopsAt = \hrtime(true);

        return $this->getTotalTime();
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalTime(): int
    {
        if (null === $this->stopsAt) {
            return self::nsecToMsec(\hrtime(true) - $this->startsAt);
        }
        return self::nsecToMsec($this->stopsAt - $this->startsAt);
    }

    /**
     * {@inheritdoc}
     */
    public function isError(): bool
    {
        return false;
    }
}
