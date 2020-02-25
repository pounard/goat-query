<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Runner\DatabaseError;

class ConfigurationError extends \RuntimeException implements DatabaseError
{
}
