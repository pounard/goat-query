<?php

declare(strict_types=1);

namespace Goat\Runner;

/**
 * @deprecated
 *   Please raise and catch \Goat\Driver\Error\TransactionError instead.
 * @see \Goat\Driver\Error\TransactionError
 */
interface TransactionFailedError extends TransactionError
{
}
