<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\Expression;
use Goat\Query\Statement;

/**
 * Represent a single value row, such as (VAL1, VAL2, ...).
 *
 * Composite types and arbitrary rows are the same thing, in the SQL standard
 * except that composite types can yield a type name in the server side, which
 * is practical for casting, converting, and such.
 *
 * But once formated, as input value as well as a output value, constant rows
 * and composite types yield the same syntax.
 *
 * There are a few differences, for named composite types, you can explicit
 * the name when sending it as an input value, or for casting, but those syntax
 * are far from being supported by various SQL vendor dialects, so for now, this
 * API considers that constant rows composite types are always the same.
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

}
