<?php

declare(strict_types=1);

namespace Goat\Runner;

class ServerError extends \RuntimeException implements DatabaseError
{
    private ?string $rawSQL = null;
    private ?string $sqlState = null;

    /**
     * Default constructor
     */
    public function __construct(string $rawSQL = null, ?string $sqlState = null, \Throwable $previous = null, ?string $errorString = null)
    {
        $this->rawSQL = $rawSQL;
        $this->sqlState = $sqlState;

        if (!$errorString) {
            if ($rawSQL) {
                $errorString = \sprintf("Error while querying backend, query was:\n%s", $rawSQL);
            } else {
                $errorString = \sprintf("Error while querying backend.");
            }
        }

        if ($previous) {
            parent::__construct($errorString, 0, $previous);
        } else {
            parent::__construct($errorString);
        }
    }

    public function getRawSql(): ?string
    {
        return $this->rawSQL;
    }

    public function getSqlState(): ?string
    {
        return $this->sqlState;
    }
}
