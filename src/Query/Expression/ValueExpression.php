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
    private $bag;
    private $type;
    private $value;

    /**
     * Default constructor
     */
    private function __construct()
    {
    }

    /**
     * Build from value
     */
    public static function create($value, ?string $type = null): self
    {
        $ret = new self;
        $ret->value = $value;
        $ret->type = $type;

        return $ret;
    }

    /**
     * Get value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get value type
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
