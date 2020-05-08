<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Partial\InsertTrait;
use Goat\Query\Partial\ReturningQueryTrait;

/**
 * Represents an INSERT INTO table SELECT ... query.
 *
 * This class is not final for backward compatibility purpose: do NOT extend it.
 */
class InsertQuery extends AbstractQuery
{
    use InsertTrait;
    use ReturningQueryTrait;

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        $arguments = new ArgumentBag();

        foreach ($this->getAllWith() as $selectQuery) {
            $arguments->append($selectQuery[1]->getArguments());
        }

        $arguments->append($this->getQuery()->getArguments());

        return $arguments;
    }
}
