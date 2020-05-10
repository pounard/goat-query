<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

class TimerAggregate implements ProfilerResult
{
    const ERROR = -1;

    private bool $pending = false;
    private bool $hasError = false;
    private float $totalTime = 0;
    private ?float $startAt = null;
    /** @var array<string,float> */
    private array $timers = [];
    /** @var array<string,float> */
    private array $timings = [];

    private function __construct()
    {
    }

    /**
     * Start timer aggregate.
     *
     * @return static
     */
    public static function start(): self
    {
        $ret = new static();
        $ret->pending = true;
        $ret->startAt = \hrtime(true);

        return $ret;
    }

    /**
     * Get empty instance.
     *
     * @return static
     */
    public static function empty(): self
    {
        return new static();
    }

    /**
     * Start an internal timer.
     */
    public function begin(string $name): void
    {
        if (\array_key_exists($name, $this->timers)) {
            $this->hasError = true;
        } else {
            $this->timers[$name] = \hrtime(true);
        }
    }

    /**
     * Stop and internal timer.
     */
    public function end(string $name): void
    {
        $timer = $this->timers[$name] ?? null;

        if (null === $timer) {
            $this->timings[$name] = -1;
            $this->hasError = true;
        } else {
            $this->timings[$name] = \hrtime(true) - $this->startAt;
            unset($this->timers[$name]);
        }
    }

    /**
     * Terminal all remaining timers.
     */
    public function stop(): void
    {
        $stopAt = \hrtime(true);
        if ($this->startAt) {
            $this->totalTime = $stopAt - $this->startAt;
        }

        foreach ($this->timers as $name => $startAt) {
            $this->timings[$name] = $stopAt - $startAt;
        }
        $this->timers = [];
    }

    /**
     * Get single value.
     *
     * This method is safe only when this timer aggregate was fully stopped.
     */
    public function get(string $name): int
    {
        $nsec = $this->timings[$name] ?? null;

        if (null === $nsec) {
            return self::ERROR;
        }

        return Timer::nsecToMsec($nsec);
    }

    /**
     * Get all timers.
     *
     * This method is safe only when this timer aggregate was fully stopped.
     */
    public function getAll(): array
    {
        return \array_map([Timer::class, 'nsecToMsec'], $this->timings);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalTime(): int
    {
        if (!$this->pending) {
            return Timer::nsecToMsec($this->totalTime);
        }
        if ($this->startAt) {
            return Timer::nsecToMsec(\hrtime(true) - $this->startAt);
        }
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isError(): bool
    {
        return $this->hasError;
    }
}
