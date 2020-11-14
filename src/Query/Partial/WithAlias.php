<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

interface WithAlias
{
    public function getAlias(): ?string;
}
