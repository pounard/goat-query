<?php

declare(strict_types=1);

namespace Goat\Query;

final class Join
{
    /** @var Statement */
    public $relation;
    /** @var Where */
    public $condition;
    /** @var int */
    public $mode;

    public function __construct(
        ExpressionRelation $relation,
        $condition = null,
        int $mode = Query::JOIN_INNER
    ){
        if (null === $condition) {
            $this->condition = new Where();
        } else if (\is_string($condition) || $condition instanceof ExpressionRaw) {
            $this->condition = (new Where())->expression($condition);
        } else {
            if (!$condition instanceof Where) {
                throw new QueryError(\sprintf("condition must be either a string or an instance of %s", Where::class));
            }
            $this->condition = $condition;
        }

        $this->relation = $relation;
        $this->mode = $mode;
    }

    public function __clone()
    {
        $this->relation = clone $this->relation;
        $this->condition = clone $this->condition;
    }
}
