<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Partial\InsertTrait;
use Goat\Query\Partial\ReturningQueryTrait;
use Goat\Query\Partial\UpsertTrait;

/**
 * Represent either one of UPDATE .. ON CONFLICT DO .. or MERGE .. queries
 * depending upon your database implementation.
 */
class MergeQuery extends AbstractQuery
{
    use InsertTrait;
    use UpsertTrait;
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
