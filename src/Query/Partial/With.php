<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\Expression;

final class With
{
    public string $alias;
    public bool $recursive;
    public Expression $table;

    public function __construct(
        string $alias,
        Expression $table,
        bool $recursive
    ) {
        $this->alias = $alias;
        $this->recursive = $recursive;
        $this->table = $table;
    }

    public function __clone()
    {
        $this->table = clone $this->table;
    }
}
