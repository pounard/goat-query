<?php

declare(strict_types=1);

namespace Goat\Runner\Testing;

use Goat\Driver\Platform\Escaper\Escaper;
use Goat\Query\QueryError;

/**
 * Does escape pretty much nothing.
 */
class NullEscaper implements Escaper
{
    private $useNumericIndices;

    public function __construct($useNumericIndices = false)
    {
        $this->useNumericIndices = (bool)$useNumericIndices;
    }

    /**
     * {@inheritdoc}
     */
    public function writePlaceholder(int $index): string
    {
        if ($this->useNumericIndices) {
            return \sprintf('#%d', $index + 1);
        }
        return '?';
    }

    /**
     * {@inheritdoc}
     */
    public function unescapePlaceholderChar(): string
    {
        return '?';
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string) : string
    {
        return '"' . $string . '"';
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifierList($strings) : string
    {
        if (!$strings) {
            throw new QueryError("cannot not format an empty identifier list");
        }
        if (!\is_array($strings)) {
            $strings = [$strings];
        }

        return \implode(', ', \array_map([$this, 'escapeIdentifier'], $strings));
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral(string $string) : string
    {
        return "'" . $string . "'";
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLike(string $string) : string
    {
        return addcslashes($string, '\%_');
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob(string $word) : string
    {
        return '#' . $word . '#';
    }

    /**
     * {@inheritdoc}
     */
    public function unescapeBlob($resource) : ?string
    {
        return \mb_substr(\mb_substr($resource, -1), 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences() : array
    {
        return ['"', "'", '$$'];
    }
}
