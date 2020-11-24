<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

use Goat\Query\Statement;

interface SqlWriter
{
    /**
     * Format query.
     */
    public function format(Statement $query, WriterContext $context): string;

    /**
     * Rewrite query by adding type cast information and correct placeholders.
     *
     * @param string|Statement $query
     *   If query is a Statement, format() will be called.
     *
     * @return FormattedQuery
     */
    public function prepare($query): FormattedQuery;
}
