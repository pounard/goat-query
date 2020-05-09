<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Query;

use Goat\Query\Expression;
use Goat\Query\ExpressionRaw;

/**
 * MySQL >= 8
 */
class MySQL8Writer extends MySQLWriter
{
    /**
     * Format excluded item from INSERT or MERGE values.
     */
    protected function doFormatInsertExcludedItem($expression): Expression
    {
        if (\is_string($expression)) {
            // Let pass strings with dot inside, it might already been formatted.
            if (false !== \strpos($expression, ".")) {
                return ExpressionRaw::create($expression);
            }
            return ExpressionRaw::create("new." . $this->escaper->escapeIdentifier($expression));
        }

        return $expression;
    }
}