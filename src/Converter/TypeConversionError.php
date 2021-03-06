<?php

declare(strict_types=1);

namespace Goat\Converter;

use Goat\Runner\DatabaseError;

/**
 * Type consersion error during query
 */
class TypeConversionError extends \RuntimeException implements DatabaseError
{
}
