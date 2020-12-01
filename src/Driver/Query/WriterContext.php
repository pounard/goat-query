<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

final class WriterContext
{
    private ArgumentBag $arguments;

    public function __construct()
    {
        $this->arguments = new ArgumentBag();
    }

    public function append($value, ?string $type = null): void
    {
        $this->arguments->add($value, $type);
    }

    public function getArgumentBag(): ArgumentBag
    {
        return $this->arguments;
    }
}
