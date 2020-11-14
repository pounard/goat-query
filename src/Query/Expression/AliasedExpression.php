<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\ArgumentBag;
use Goat\Query\Expression;
use Goat\Query\Partial\WithAlias;
use Goat\Query\Partial\WithAliasTrait;

/**
 * Holds a non aliased expression along an expression.
 */
class AliasedExpression implements Expression, WithAlias
{
    use WithAliasTrait;

    private Expression $expression;

    public function __construct(string $alias, Expression $expression)
    {
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
