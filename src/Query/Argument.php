<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Store a single query parameter, along with its type.
 */
final class Argument
{
    private $value;
    private $type = null;

    public function __construct($value, ?string $type = null)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function getType(): ?string
    {
        return $this->type;
    }
}
