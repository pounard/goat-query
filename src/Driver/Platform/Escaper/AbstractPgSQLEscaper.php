<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Escaper;

abstract class AbstractPgSQLEscaper extends AbstractEscaper
{
    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences(): array
    {
        return [
            '"',  // Identifier escape character
            '\'', // String literal escape character
            '$$', // String constant escape sequence
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string): string
    {
        // See https://www.postgresql.org/docs/10/sql-syntax-lexical.html#SQL-SYNTAX-IDENTIFIERS
        return '"'.\str_replace('"', '""', $string).'"';
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral(string $string): string
    {
        // See https://www.postgresql.org/docs/10/sql-syntax-lexical.html#SQL-SYNTAX-STRINGS
        //
        // PostgreSQL have different ways of escaping:
        //   - String constant, using single quote as a the delimiter, is an
        //     easy one since doubling all the quotes will work for escaping
        //     as long as you do not have dangling \ chars within.
        //   - String constant prefixed using E (eg. E'foo') will ignore the
        //     inside \ chars, and allows you to inject stuff such as \n.
        //   - String constants with unicodes escapes (we won't deal with it).
        //   - Dollar-quoted string constants (we won't deal with it).
        //
        // For convenience, default implementation will only naively handle
        // the first use case, because escaped C chars will already be
        // interpreted as the corresponding character by PHP, and what we get
        // is actually a binary UTF-8 string in most cases.
        //
        // Please be aware that in the end, the driver will override this in
        // most case and this code will be executed.
        return "'".\str_replace("'", "''", $string)."'";
    }
}
