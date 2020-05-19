<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * @deprecated
 *   Use MergeQuery directly instead.
 * @todo
 *   Add deprecation messages.
 */
final class UpsertValuesQuery extends MergeQuery
{
    public function getValueCount(): int
    {
        $query = $this->getQuery();

        if ($query instanceof ExpressionConstantTable) {
            return $query->getRowCount();
        }

        return 0;
    }
}
