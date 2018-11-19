<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * A statement is something that the SQL database can execute. It does not
 * always return values, but it can return multiple values.
 */
interface Statement
{
    /**
     * Get query arguments
     *
     * Those arguments will be later converted by the driven prior to the
     * query being sent to the backend; for this to work type cast information
     * must lie into the query
     */
    public function getArguments(): ArgumentBag;
}
