<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

final class NullProfiler implements Profiler
{
    /**
     * {@inheritdoc}
     */
    public function add(ProfilerResult $result)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function all(): iterable
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
    }
}
