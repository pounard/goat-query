<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\Expression;
use Goat\Query\Statement;

/**
 * Represents a statement(s) comparison.
 */
class ComparisonExpression implements Expression
{
    private ?Statement $left;
    private ?Statement $right;
    private ?string $operator;

    public function __construct(?Statement $left, ?Statement $right, ?string $operator)
    {
        $this->left = $left;
        $this->right = $right;
        $this->operator = $operator;
    }

    public function getLeft(): ?Statement
    {
        return $this->left;
    }


    public function getRight(): ?Statement
    {
        return $this->right;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }
}
