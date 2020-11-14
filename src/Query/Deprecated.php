<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Expression\TableExpression;

/**
 * @deprecated
 * @see Goat\Query\Expression\TableExpression
 */
final class ExpressionRelation extends TableExpression
{
}

/**
 * @deprecated
 *   Use InsertQuery directly instead.
 * @todo
 *   Add deprecation messages.
 */
final class InsertQueryQuery extends InsertQuery
{
}

/**
 * @deprecated
 *   Use InsertQuery directly instead.
 * @todo
 *   Add deprecation messages.
 */
final class InsertValuesQuery extends InsertQuery
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

/**
 * @deprecated
 *   Use MergeQuery directly instead.
 * @todo
 *   Add deprecation messages.
 */
final class UpsertQueryQuery extends MergeQuery
{
}

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
