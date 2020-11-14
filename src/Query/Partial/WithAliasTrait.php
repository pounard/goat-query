<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

trait WithAliasTrait /* implements WithAlias */
{
    private ?string $alias = null;

    public function getAlias(): ?string
    {
        return $this->alias;
    }
}
