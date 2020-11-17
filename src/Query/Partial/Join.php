<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\Expression;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\Where;
use Goat\Query\Expression\RawExpression;

final class Join
{
    public Expression $table;
    public Where $condition;
    public int $mode;

    public function __construct(
        Expression $table,
        $condition = null,
        int $mode = Query::JOIN_INNER
    ) {
        // @todo
        //   we must not check $condition type here, but let Where instance
        //   normalize it transparently in the ->expression() method:
        //     - if a callback, where should check for callback return, if return
        //       is the same as the parameter, ignore it, it means user used a short
        //       arrow function
        //     - otherwise the callback should return a where
        //     - and all the other checks
        if (null === $condition) {
            $this->condition = new Where();
        } else if (\is_string($condition) || $condition instanceof RawExpression) {
            $this->condition = (new Where())->expression($condition);
        } else {
            if (!$condition instanceof Where) {
                throw new QueryError(\sprintf("\$condition must be either a string or an instance of %s", Where::class));
            }
            $this->condition = $condition;
        }

        $this->table = $table;
        $this->mode = $mode;
    }

    public function __clone()
    {
        $this->table = clone $this->table;
        $this->condition = clone $this->condition;
    }
}
