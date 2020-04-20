<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Escaper;

class PDOPgSQLEscaper extends AbstractPgSQLEscaper
{
    use PDOEscaperTrait;

    /**
     * {@inheritdoc}
     */
    protected function areIdentifiersSafe(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function unescapePlaceholderChar(): string
    {
        return '??';
    }
}
