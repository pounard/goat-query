<?php

declare(strict_types=1);

namespace Goat\Runner\Testing;

use Psr\Log\AbstractLogger;

final class ArrayLogger extends AbstractLogger
{
    private $messages = [];

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        $this->messages[$level][] = [$message, $context];
    }

    public function getMessageCount($level): int
    {
        return \count($this->messages[$level] ?? []);
    }

    public function getMessages($level): array
    {
        return $this->messages[$level] ?? [];
    }
}
