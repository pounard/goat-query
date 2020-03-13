<?php

declare(strict_types=1);

namespace Goat\Runner\Metadata;

interface ResultProfile
{
    /**
     * Get preparation time, in milliseconds.
     */
    public function getPreparationTime(): int;

    /**
     * Get execution time, in milliseconds.
     */
    public function getExecutionTime(): int;

    /**
     * Get total time, in milliseconds.
     */
    public function getTotalTime(): int;

    /**
     * Was this result profile cleanly updated.
     */
    public function isComplete(): bool;

    /**
     * Was this query an error.
     */
    public function isError(): bool;
}
