<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\Expression;
use Goat\Query\ValueRepresentation;

/**
 * Represents a raw value, along with an optional type.
 *
 * Value type will be used for the value converter, but will not change
 * anything in the SQL side.
 *
 * Value itself can be anything including an Expression instance.
 */
class ValueExpression implements Expression, ValueRepresentation
{
    private ?string $type = null;
    /** @var mixed */
    private $value;

    public function __construct($value, ?string $type = null)
    {
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * Get value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get value type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }
}
