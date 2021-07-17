<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Raw query is strictly identical to RawExpression with the additional
 * implementation of the Query interface.
 */
class RawQuery extends AbstractQuery
{
    private string $expression;
    private array $arguments;

    public function __construct(string $expression, $arguments = [])
    {
        if (!\is_array($arguments)) {
            $arguments = [$arguments];
        } else {
            $arguments = \array_values($arguments);
        }

        $this->expression = $expression;
        $this->arguments = $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function willReturnRows(): bool
    {
        // Since we cannot predict what the user will write here, it's safe
        // to always return true here, the only consequence is that it might
        // bypass a few optimisations in rare cases.
        return true;
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
