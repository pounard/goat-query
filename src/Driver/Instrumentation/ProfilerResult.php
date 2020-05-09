<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

interface ProfilerResult
{
    /**
     * Get duration in milliseconds.
     *
     * If timer was not stopped, this will return the current timing.
     */
    public function getTotalTime(): int;

    /**
     * Was this operation an error?
     */
    public function isError(): bool;
}
