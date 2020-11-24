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

    public function append(array $arguments): void
    {
        $this->arguments->addAll($arguments);
    }

    public function getArgumentBag(): ArgumentBag
    {
        return $this->arguments;
    }
}
