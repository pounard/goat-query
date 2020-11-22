<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\ArgumentBag;
use Goat\Query\Expression;

/**
 * Raw user-given SQL string.
 *
 * SECURITY WARNING: THIS WILL NEVER BE ESCAPED, IN ANY CASES.
 */
class RawExpression implements Expression
{
    private string $expression;
    private ArgumentBag $arguments;

    public function __construct(string $expression, $arguments = [])
    {
        if (!\is_array($arguments)) {
            $arguments = [$arguments];
        }

        $this->expression = $expression;
        $this->arguments = new ArgumentBag();
        $this->arguments->appendArray($arguments);
    }

    /**
     * Get raw SQL string
     */
    public function getString(): string
    {
        return $this->expression;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        return $this->arguments;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        $this->arguments = clone $this->arguments;
    }
}
