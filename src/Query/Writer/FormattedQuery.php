<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

use Goat\Converter\ConverterInterface;
use Goat\Query\ArgumentBag;
use Goat\Query\QueryError;

/**
 * Carries a formatted query for a driver, along with its arguments
 */
final class FormattedQuery
{
    private $arguments;
    private $converter; // @todo remove this dependency
    private $query;

    /**
     * Default constructor
     */
    public function __construct(string $query, ArgumentBag $arguments = null)
    {
        $this->arguments = $arguments ?? new ArgumentBag();
        $this->arguments->lock();
        $this->query = $query;
    }

    /**
     * @deprecated
     *   Remove me, once converter dependency as been removed
     *   from the FormatterBase
     */
    public function setConverter(?ConverterInterface $converter = null): self
    {
        $this->converter = $converter;

        return $this;
    }

    /**
     * Prepare arguments using given values
     */
    public function getArguments(?ConverterInterface $converter = null, array $arguments = null): array
    {
        if ($arguments) {
            $bag = $this->arguments->merge($arguments);
        } else {
            $bag = $this->arguments;
        }
        $args = $bag->getAll();

        $converter = $converter ?? $this->converter;

        foreach ($args as $index => $value) {
            if ($converter) {
                $args[$index] = $converter->toSQL($bag->getTypeAt($index) ?? ConverterInterface::TYPE_UNKNOWN, $value);
            } else if (null !== $value && !\is_scalar($value)) {
                throw new QueryError(\sprintf("argument '%s' must be a string, '%s' given", $index, \gettype($value)));
            }
        }

        return $args;
    }

    /**
     * Get formatted and escaped SQL query with placeholders
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}
