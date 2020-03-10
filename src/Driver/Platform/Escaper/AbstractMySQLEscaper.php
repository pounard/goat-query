<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Escaper;

/**
 * MySQL is a very tought beast to tame, we will never, ever, attempt to escape
 * strings ourselves, because it will always try to interpret hex chars, unicode
 * chars, and any other variant it supports behind our back, leaving wide opened
 * very hardcore security issues. When you use MySQL, the driver MUST ALWAYS
 * escape strings by itself and you MUST ALWAYS use prepared statements.
 * End of line.
 */
abstract class AbstractMySQLEscaper extends AbstractEscaper
{
    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences(): array
    {
        return [
            '`',  // Identifier escape character
            '\'', // String literal escape character
            '"',  // String literal variant
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string): string
    {
        return '`'.\str_replace('`', '``', $string).'`';
    }
}
