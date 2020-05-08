<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Partial\InsertValuesTrait;
use Goat\Query\Partial\ReturningQueryTrait;

/**
 * Represents an INSERT INTO table (...) VALUES (...) [, (...)] query
 */
final class InsertValuesQuery extends AbstractQuery
{
    use InsertValuesTrait;
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

        $arguments->append($this->arguments);

        return $arguments;
    }
}
