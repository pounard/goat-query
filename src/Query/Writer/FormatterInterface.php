<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

use Goat\Query\ArgumentBag;
use Goat\Query\Statement;

/**
 * SQL formatter
 */
interface FormatterInterface
{
    /**
     * Set escaper
     */
    public function setEscaper(EscaperInterface $escaper): void;

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
     * @param string|\Goat\Query\Statement $query
     * @param ?ArgumentBag $arguments
     *   Holds type information
     *
     * @return FormattedQuery
     */
    public function prepare($query): FormattedQuery;
}
