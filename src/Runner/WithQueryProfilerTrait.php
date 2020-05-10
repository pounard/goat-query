<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Driver\Instrumentation\QueryProfiler;

trait WithQueryProfilerTrait
{
    private ?QueryProfiler $profiler = null;

    /**
     * {@inheritdoc}
     */
    public function getQueryProfiler(): QueryProfiler
    {
        return $this->profiler ?? QueryProfiler::empty();
    }

    /**
     * @internal
     */
    public function setQueryProfiler(QueryProfiler $profiler): void
    {
        $this->profiler = $profiler;
    }
}

