<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\Expression;
use Goat\Query\Partial\WithAlias;
use Goat\Query\Partial\WithAliasTrait;

class TableExpression implements Expression, WithAlias
{
    use WithAliasTrait;

    private string $name;
    private ?string $schema = null;

    /**
     * @param string|IdentifierExpression $name
     */
    public function __construct($name, ?string $alias = null, ?string $schema = null)
    {
        if (null === $schema) {
            if ($name instanceof IdentifierExpression) {
                $name = $name->getName();
            } else if (false !== \strpos($name, '.')) {
                list($schema, $name) = \explode('.', $name, 2);
            }
        }

        $this->alias = $alias;
        $this->name = $name;
        $this->schema = $schema;
    }

    /**
     * Get table name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get schema.
     */
    public function getSchema(): ?string
    {
        return $this->schema;
    }
}
