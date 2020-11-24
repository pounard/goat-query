<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\Expression;

/**
 * Raw user-given SQL string.
 *
 * SECURITY WARNING: THIS WILL NEVER BE ESCAPED, IN ANY CASES.
 */
class RawExpression implements Expression
{
    private string $expression;
    private array $arguments;

    public function __construct(string $expression, $arguments = [])
    {
        if (!\is_array($arguments)) {
            $arguments = [$arguments];
        }

        $this->expression = $expression;
        $this->arguments = $arguments;
    }

    /**
     * Get raw SQL string
     */
    public function getString(): string
    {
        return $this->expression;
    }

    /**
     * Get arguments.
     *
     * @return mixed[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Deep clone support.
     */
    public function __clone()
    {
        foreach ($this->arguments as $index => $value) {
            $this->arguments[$index] = \is_object($value) ? clone $value : $value;
        }
    }
}
