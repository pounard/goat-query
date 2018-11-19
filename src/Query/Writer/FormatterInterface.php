<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

use Goat\Converter\ConverterInterface;
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
     * Set converter
     */
    public function setConverter(ConverterInterface $converter): void;

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
     * @param mixed[]|\Goat\Query\ArgumentBag $parameters
     *
     * @return FormattedQuery
     */
    public function prepare($query, array $parameters = null): FormattedQuery;
}
