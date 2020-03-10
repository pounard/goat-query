<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

use Goat\Query\Statement;

interface SqlWriter
{
    /**
     * Format the query
     *
     * @param Statement $query
     *
     * @return string
     */
    public function format(Statement $query): string;

    /**
     * Rewrite query by adding type cast information and correct placeholders
     *
     * @param string|Statement $query
     *
     * @return FormattedQuery
     */
    public function prepare($query): FormattedQuery;
}
