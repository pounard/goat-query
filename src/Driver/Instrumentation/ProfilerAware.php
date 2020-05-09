<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

interface ProfilerAware
{
    /**
     * Get profiler.
     */
    public function getProfiler(): Profiler;

    /**
     * Replace profiler and return previous one.
     */
    public function setProfiler(Profiler $profiler): Profiler;
}
