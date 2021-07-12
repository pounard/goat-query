<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

final class WriterContext
{
    private ArgumentBag $arguments;
    private $currentIndex = 0;

    public function __construct()
    {
        $this->arguments = new ArgumentBag();
    }

    public function append($value, ?string $type = null): int
    {
        $this->arguments->add($value, $type);

        return $this->currentIndex++;
    }

    public function getCurrentIndex(): int
    {
        return $this->currentIndex;
    }

    public function getArgumentBag(): ArgumentBag
    {
        return $this->arguments;
    }
}
