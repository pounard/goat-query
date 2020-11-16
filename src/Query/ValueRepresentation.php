<?php

declare(strict_types=1);

namespace Goat\Query;

interface ValueRepresentation
{
    /** @return mixed */
    public function getValue();

    public function getType(): ?string;
}
