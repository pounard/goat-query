<?php

declare(strict_types=1);

namespace Goat\Driver\Error;

use Goat\Query\QueryError;
use Goat\Runner\TransactionFailedError;

class TransactionError extends QueryError implements TransactionFailedError
{
    public static function fromException(\Throwable $previous): self
    {
        return new self("Error while in transaction: " . $previous->getMessage(), $previous->getCode(), $previous);
    }
}
