<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Represents a raw value
 */
final class Value implements ValueRepresentation
{
    private $name;
    private $type;
    private $value;

    /**
     * Build from value
     */
    public function __construct($value, ?string $type = null, ?string $name = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}
