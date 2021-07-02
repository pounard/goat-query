<?php

declare(strict_types=1);

namespace Goat\Runner;

use MakinaCorpus\Profiling\Profiler;
use MakinaCorpus\Profiling\Implementation\DefaultProfiler;

trait WithQueryProfilerTrait
{
    private ?Profiler $profiler = null;

    /**
     * {@inheritdoc}
     */
    public function getQueryProfiler(): Profiler
    {
        return $this->profiler ?? new DefaultProfiler();
    }

    /**
     * @internal
     */
    public function setQueryProfiler(Profiler $profiler): void
    {
        $this->profiler = $profiler;
    }
}

