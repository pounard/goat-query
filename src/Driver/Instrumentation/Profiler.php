<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

interface Profiler
{
    /**
     * Append result.
     */
    public function add(ProfilerResult $result);

    /**
     * @return ProfilerResult[]
     */
    public function all(): iterable;

    /**
     * Clear data of current profiler (free memory).
     */
    public function clear(): void;
}
