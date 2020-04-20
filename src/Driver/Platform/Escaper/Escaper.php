<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Escaper;

/**
 * Escaper holds two different responsabilities, but they cannot be divided:
 *
 *   - some escaping functions can be wrote in plain PHP and will depend upon
 *     the database server and version, in other words, the platform,
 *
 *   - some escaping functions will depend upon the connection library, in other
 *     words, PDO, ext-pgsql, ...,
 *
 *   - which escaping function is concerned by which of the above statements
 *     will depend upon the low level connection library.
 *
 * Due to this fact, the escaper cannot be part of the platform, but must
 * injected to it by the driver, which means that in certain cases, the escaper
 * will require the runner to be spawn, which makes us in front of a chicken and
 * problem.
 */
interface Escaper
{
    /**
     * Escape identifier (ie. table name, variable name, ...)
     */
    public function escapeIdentifier(string $string): string;

    /**
     * Escape identifier list (ie. table name, variable name, ...)
     *
     * @param string|string[] $strings
     *
     * @return $string
     *   Comma-separated list
     */
    public function escapeIdentifierList($strings): string;

    /**
     * Escape literal (string)
     */
    public function escapeLiteral(string $string): string;

    /**
     * Escape like (string)
     */
    public function escapeLike(string $string): string;

    /**
     * Get backend escape sequences
     *
     * Escape sequences are only used by the SQL-rewriting method that proceed
     * to parameters cast and replacement.
     *
     * @return string[]
     */
    public function getEscapeSequences(): array;

    /**
     * Get the default anonymous placeholder for queries
     *
     * @param int $index
     *   The numerical index position of the placeholder value
     *
     * @return string
     *   The placeholder
     */
    public function writePlaceholder(int $index): string;

    /**
     * Since that "?" is escaped in our queries by writing "??", this method
     * is supposed to restore "?" back. But some drivers, such as PDO, will
     * need to re-escape "?" to "??".
     *
     * For PDO, return "??", and all others just "?".
     */
    public function unescapePlaceholderChar(): string;

    /**
     * Escape blob properly for driver
     */
    public function escapeBlob(string $word): string;

    /**
     * Unescape blob coming from query result.
     *
     * Some backends such as PDO if configured for may send back a stream
     * and not a string (which seems to be a sane behaviour especially with
     * large blobs) and others backends will send encoded data in such a way
     * it needs a special decoding ability.
     */
    public function unescapeBlob($resource): ?string;
}
