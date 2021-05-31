<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Escaper;

use Goat\Driver\ConfigurationError;
use Goat\Driver\Runner\ExtPgSQLErrorTrait;

/**
 * The escaper seems like the right place to do this, platform must be
 * connection independent (even if it's not completely, due to this escaper
 * dependency).
 */
class ExtPgSQLEscaper extends AbstractPgSQLEscaper
{
    use ExtPgSQLErrorTrait;
    /** @var resource<\pg_connect> */
    private $connection;

    public function __construct($connection)
    {
        if (!\is_resource($connection)) {
            throw new ConfigurationError("\$connection parameter must be a \\pg_connect() opened resource.");
        }
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function writePlaceholder(int $index) : string
    {
        return '$' . ($index + 1);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string): string
    {
        // @todo This should be tested for performance.
        // return '"' . \str_replace('"', '""', $string) . '"';

        if ('' === $string) {
            return '';
        }

        $escaped = @\pg_escape_identifier($this->connection, $string);
        if (false === $escaped) {
            $this->serverError($this->connection);
        }

        return $escaped;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral(string $string): string
    {
        if ('' === $string) {
            return '';
        }

        $escaped = @\pg_escape_literal($this->connection, $string);
        if (false === $escaped) {
            $this->serverError($this->connection);
        }

        return $escaped;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob(string $word): string
    {
        if ('' === $word) {
            return '';
        }

        $escaped = @\pg_escape_bytea($this->connection, $word);
        if (false === $escaped) {
            $this->serverError($this->connection);
        }

        return $escaped;
    }

    /**
     * {@inheritdoc}
     */
    public function unescapeBlob($resource): ?string
    {
        if ('' === $resource || null === $resource) {
            return $resource;
        }

        $unescaped = @\pg_unescape_bytea($resource);
        if (false === $unescaped) {
            $this->serverError($this->connection);
        }

        return $unescaped;
    }
}
