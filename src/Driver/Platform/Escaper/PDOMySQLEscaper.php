<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Escaper;

class PDOMySQLEscaper extends AbstractMySQLEscaper
{
    use PDOEscaperTrait;

    /**
     * {@inheritdoc}
     */
    protected function areIdentifiersSafe(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function unescapePlaceholderChar(): string
    {
        return '??';
    }
}
