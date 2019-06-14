<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

use Goat\Query\ArgumentList;

/**
 * Carries a formatted query for a driver, along with its arguments types
 */
final class FormattedQuery
{
    private $rawSQL;
    private $argumentList;

    /**
     * Default constructor
     */
    public function __construct(string $rawSQL, ArgumentList $argumentList)
    {
        $this->argumentList = $argumentList;
        $this->rawSQL = $rawSQL;
    }

    /**
     * Get argument count
     */
    public function getArgumentList(): ArgumentList
    {
        return $this->argumentList;
    }

    /**
     * Get formatted and escaped SQL query with placeholders
     */
    public function getRawSQL(): string
    {
        return $this->rawSQL;
    }
}
