<?php

declare(strict_types=1);

namespace Goat\Runner\Driver;

class DriverError extends \RuntimeException
{
    private $rawSQL;
    private $parameters;

    /**
     * Default constructor
     */
    public function __construct($rawSQL, $parameters = null, \Throwable $previous = null)
    {
        $this->rawSQL = $rawSQL;
        $this->parameters = $parameters;

        $message = \sprintf("error while querying backend, query is:\n%s", $rawSQL);

        parent::__construct($message, null, $previous);
    }
}
