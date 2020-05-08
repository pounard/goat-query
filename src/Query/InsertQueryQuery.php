<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Partial\InsertQueryTrait;
use Goat\Query\Partial\ReturningQueryTrait;

/**
 * Represents an INSERT INTO table SELECT ... query
 */
final class InsertQueryQuery extends AbstractQuery
{
    use InsertQueryTrait;
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

        $arguments->append($this->query->getArguments());

        return $arguments;
    }
}
