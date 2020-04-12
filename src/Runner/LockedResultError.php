<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Query\QueryError;

class LockedResultError extends QueryError
{
    public function __construct($message = null, $code = null, $previous = null)
    {
        if (!$message) {
            $message = "You cannot change an iterator when iteration has started.";
        }
        if (null === $code) {
            $code = 0;
        }

        // Avoid stupid PHP errors when passing null for previous.
        if ($previous) {
            parent::__construct($message, $code, $previous);
        } else {
            parent::__construct($message, $code);
        }
    }
}
