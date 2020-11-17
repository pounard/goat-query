<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Raw user-given SQL string.
 *
 * SECURITY WARNING: THIS WILL NEVER BE ESCAPED, IN ANY CASES.
 */
final class ExpressionRaw implements Expression
{
    private string $expression;
    private ArgumentBag $arguments;

    /**
     * Default constructor
     *
     * @param string $expressionString
     *   Raw SQL expression string
     * @param mixed[] $arguments
     *   Key/value pairs or argument list, anonymous and named parameters
     *   cannot be mixed up within the same query
     *
     * @deprecated
     *   Use static create() method instead.
     */
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
     * Create instance from name and alias
     */
    public static function create(string $expression, $arguments = []): self
    {
        return new self($expression, $arguments);
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
