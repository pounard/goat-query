<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

use Goat\Query\Statement;

interface SqlWriter
{
    /**
     * Format, aggregate arguments, then prepare for driver any query builder
     * built query, arbitrary expression, or raw SQL string.
     *
     * Rewrite the resulting SQL string or query, replace ?::TYPE information
     * using driver specific placeholders, and store found types information in
     * the result object.
     *
     * @param string|Statement $query
     *
     * @return FormattedQuery
     */
    public function prepare($query): FormattedQuery;
}
