<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Runner\DatabaseError;

class QueryError extends \RuntimeException implements DatabaseError
{
}
