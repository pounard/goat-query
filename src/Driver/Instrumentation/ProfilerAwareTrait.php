<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

trait ProfilerAwareTrait
{
    private Profiler $profiler;

    /**
     * Get profiler.
     */
    public function getProfiler(): Profiler
    {
        return $this->profiler ?? ($this->profiler = new Profiler());
    }

    /**
     * Replace profiler and return previous one.
     */
    public function setProfiler(Profiler $profiler): Profiler
    {
        $previous = $this->getProfiler();
        $this->profiler = $profiler;

        return $previous;
    }

    protected function startProfilerQuery(): QueryProfiler
    {
        $profiler = QueryProfiler::start();
        $this->getProfiler()->add($profiler);

        return $profiler;
    }

    protected function startProfilerTimerAggregate(): TimerAggregate
    {
        $profiler = TimerAggregate::start();
        $this->getProfiler()->add($profiler);

        return $profiler;
    }
}
