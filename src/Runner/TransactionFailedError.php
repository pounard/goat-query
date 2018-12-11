<?php

declare(strict_types=1);

namespace Goat\Runner;

/**
 * Transaction COMMIT operation failed, or a constraint was violated during
 * transaction lifetime.
 */
class TransactionFailedError extends TransactionError
{
}
