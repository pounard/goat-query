<?php

declare(strict_types=1);

namespace Goat\Runner;

class ServerError extends \RuntimeException implements DatabaseError
{
    private ?string $rawSQL = null;
    private ?array $parameters = null;

    /**
     * Default constructor
     */
    public function __construct($rawSQL, $parameters = null, \Throwable $previous = null)
    {
        $this->rawSQL = $rawSQL;
        $this->parameters = (array)$parameters;

        $message = \sprintf("error while querying backend, query is:\n%s", $rawSQL);

        if ($previous) {
            parent::__construct($message, 0, $previous);
        } else {
            parent::__construct($message);
        }
    }
}
