<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Escaper;

use Goat\Query\QueryError;

abstract class AbstractEscaper implements Escaper
{
    /**
     * {@inheritdoc}
     */
    final public function escapeIdentifierList($strings): string
    {
        if (!$strings) {
            throw new QueryError("Cannot not format an empty identifier list.");
        }
        if (\is_array($strings)) {
            return \implode(', ', \array_map([$this, 'escapeIdentifier'], $strings));
        }
        return $this->escapeIdentifier($strings);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLike(string $string): string
    {
        return \addcslashes($string, '\%_');
    }
}
