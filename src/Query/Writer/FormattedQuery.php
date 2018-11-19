<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

use Goat\Query\QueryError;

/**
 * Carries a formatted query for a driver, along with its arguments
 */
final class FormattedQuery
{
    private $query;
    private $arguments;

    /**
     * Default constructor
     */
    public function __construct(string $query, ?array $arguments = null)
    {
        $this->query = $query;
        $this->arguments = $arguments ?? [];

        if ($arguments) {
            \array_walk($arguments, function ($value, $key) {
                if (null !== $value && !\is_scalar($value)) {
                    throw new QueryError(\sprintf("argument '%s' must be a string, '%s' given", $key, \gettype($value)));
                }
            });
        }
    }

    /**
     * Get formatted and escaped SQL query with placeholders
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Get converted array of arguments
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
