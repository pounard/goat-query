<?php

declare(strict_types=1);

namespace Goat\Driver\Instrumentation;

/**
 * Extends TimerAggregate for holding timings, and will hold more metadata
 * for debugging, such as generated SQL sent to the server.
 */
class QueryProfiler extends TimerAggregate
{
}
