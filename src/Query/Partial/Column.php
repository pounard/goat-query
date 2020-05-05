<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\Expression;
use Goat\Query\ExpressionFactory;
use Goat\Query\QueryError;
use Goat\Query\Statement;

final class Column
{
    /** @var Statement */
    public $expression;
    /** @var null|string */
    public $alias;

    public function __construct(Statement $expression, ?string $alias)
    {
        $this->expression = $expression;
        $this->alias = $alias;
    }

    public static function name($expression, ?string $alias) 
    {
        return new self(ExpressionFactory::column($expression), $alias);
    }

    public static function expression($expression, ?string $alias, $arguments = [])
    {
        if ($expression instanceof Expression) {
            if ($arguments) {
                throw new QueryError(\sprintf("you cannot call %s::columnExpression() and pass arguments if the given expression is not a string", __CLASS__));
            }
        } else {
            if ($arguments && !\is_array($arguments)) {
                $arguments = [$arguments];
            }
            $expression = ExpressionFactory::raw($expression, $arguments ?? []);
        }

        return new self($expression, $alias);
    }

    public function __clone()
    {
        $this->expression = clone $this->expression;
    }
}
