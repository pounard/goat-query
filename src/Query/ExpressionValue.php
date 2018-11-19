<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Represents a raw value
 */
final class ExpressionValue implements Expression
{
    private $name;
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

        // Empty values such as '' are considered as null
        if (!$type) {
            if (\is_string($value) && $value &&  ':' === $value[0]) {

                // Attempt to find type by convention
                if (false !== \strpos($value, '::')) {
                    list($name, $type) = \explode('::', $value, 2);
                } else {
                    $name = $value;
                }

                $ret->name = \substr($name, 1);

                // Value cannot exist from this point, really, since we just
                // gave name and type information; query will need to be send
                // with parameters along
                $value = null;
            }
        }

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
     * Get value name, if any
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        $ret = new ArgumentBag();
        $ret->add($this->value, $this->name, $this->type);

        return $ret;
    }
}
