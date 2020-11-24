<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\Expression;
use Goat\Query\Statement;

/**
 * Represents a between comparison.
 */
class BetweenExpression implements Expression
{
    private Statement $column;
    private Statement $from;
    private Statement $to;
    private string $operator;

    public function __construct(Statement $column, Statement $from, Statement $to, ?string $operator)
    {
        $this->column = $column;
        $this->from = $from;
        $this->to = $to;
        $this->operator = $operator;
    }

    public function getColumn(): Statement
    {
        return $this->column;
    }

    public function getFrom(): Statement
    {
        return $this->from;
    }

    public function getTo(): Statement
    {
        return $this->to;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }
}
