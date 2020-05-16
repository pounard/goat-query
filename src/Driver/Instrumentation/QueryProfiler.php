<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

/**
 * Extends TimerAggregate for holding timings, and will hold more metadata
 * for debugging, such as generated SQL sent to the server.
 */
class QueryProfiler extends TimerAggregate
{
    private ?string $sqlQuery = null;
    private ?array $sqlArguments = null;

    public function setRawSql(string $query, ?array $arguments = null): void
    {
        $this->sqlQuery = $query;
        $this->sqlArguments = $arguments;
    }

    public function getSqlQuery(): ?string
    {
        return $this->sqlQuery;
    }

    public function getSqlArguments(): ?array
    {
        return $this->sqlArguments;
    }
}
