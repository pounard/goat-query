<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

final class Profiler
{
    private array $data = [];

    /**
     * Append result.
     */
    public function add(ProfilerResult $result)
    {
        $this->data[] = $result;
    }

    /**
     * @return ProfilerResult[]
     */
    public function all(): iterable
    {
        return $this->data;
    }

    /**
     * Clear data of current profiler (free memory).
     */
    public function clear(): void
    {
        $this->data = [];
    }
}
