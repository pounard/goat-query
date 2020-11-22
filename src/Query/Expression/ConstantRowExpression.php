<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\ArgumentBag;
use Goat\Query\Expression;
use Goat\Query\Statement;

/**
 * Represent a single value row, such as (VAL1, VAL2, ...).
 */
class ConstantRowExpression implements Expression
{
    /** @var Statement[] */
    private array $values = [];

    /**
     * Create a row expression.
     *
     * @param iterable $values
     *   Can contain pretty much anything, keys will be dropped.
     */
    public function __construct(iterable $values)
    {
        foreach ($values as $value) {
            if (!$value instanceof Statement) {
                $value = new ValueExpression($value);
            }
            $this->values[] = $value;
        }
    }

    /**
     * Get column count.
     */
    public function getColumnCount(): int
    {
        return \count($this->values);
    }

    /**
     * Get this row values.
     *
     * @return Statement[]
     */
    public function getValues(): iterable
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        $ret = new ArgumentBag();

        foreach ($this->values as $value) {
            \assert($value instanceof Statement);

            $ret->append($value->getArguments());
        }

        return $ret;
    }
}
