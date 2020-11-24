<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\Expression;

/**
 * To be used in rare case where identifiers could be interpreted,
 * such as table names, where dot is supposed to separate schema
 * name from table name.
 */
class IdentifierExpression implements Expression
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get identifier name.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
