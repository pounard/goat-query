<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Represents a raw value
 */
interface ValueRepresentation
{
    /**
     * Get value
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Get value type
     */
    public function getType(): ?string;

    /**
     * Get value name, if any
     */
    public function getName(): ?string;
}
