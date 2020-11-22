<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\ArgumentBag;
use Goat\Query\Expression;
use Goat\Query\ValueRepresentation;

/**
 * Represents a raw value.
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

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        $ret = new ArgumentBag();
        $ret->add($this->value, null, $this->type);

        return $ret;
    }
}
