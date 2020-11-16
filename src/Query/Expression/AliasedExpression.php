<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\ArgumentBag;
use Goat\Query\Expression;
use Goat\Query\Partial\WithAlias;
use Goat\Query\Partial\WithAliasTrait;
use Goat\Query\QueryError;

/**
 * Allows to hold any expression with an alias.
 */
final class AliasedExpression implements Expression, WithAlias
{
    use WithAliasTrait;

    private Expression $expression;

    public function __construct(string $alias, Expression $expression)
    {
        // This an arbitrary choice, but it prevent completely infinite loops
        // and may help bugguy normalization algorithms detection.
        if ($expression instanceof self) {
            throw new QueryError(\sprintf("'%s' instanceof cann hold a '%s' expression.", __CLASS__, __CLASS__));
        }

        $this->alias = $alias;
        $this->expression = $expression;
    }

    public function getExpression(): Expression
    {
        return $this->expression;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        return new ArgumentBag();
    }
}
