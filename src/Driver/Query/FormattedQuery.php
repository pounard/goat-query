<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

use Goat\Converter\ConverterInterface;
use Goat\Converter\ConverterContext;

/**
 * Result of SQL formatter, holds rewritten SQL containing driver specific
 * placeholders, and found arguments with types when they could be infered.
 */
final class FormattedQuery
{
    private ?string $identifier;
    private string $rawSQL;
    private ?ArgumentBag $arguments = null;

    public function __construct(
        string $rawSQL,
        ?string $identifier = null,
        ?ArgumentBag $arguments = null
    ) {
        $this->arguments = $arguments ?? new ArgumentBag();
        $this->identifier = $identifier;
        $this->rawSQL = $rawSQL;
    }

    /**
     * Get query arguments types.
     *
     * @return array<int,string|null>
     *   Keys are ordered argument index in SQL, values are found argument
     *   types, if no type found, value can be null.
     */
    public function getArgumentTypes(): array
    {
        return $this->arguments->getTypes();
    }

    /**
     * Get query identifier.
     */
    public function getIdentifier(): string
    {
        return $this->identifier ?? ($this->identifier = 'gqi_'.\md5($this->rawSQL));
    }

    /**
     * Prepare arguments with the given input.
     */
    public function prepareArgumentsWith(ConverterContext $context, array $arguments = null): array
    {
        // If null was given, this means that we should use query given
        // arguments, those who are already set in this object.
        // Sometime, we receive an empty array due to wrong typings that
        // must be fixed, until then, we also consider empty arrays as
        // being null.
        if ($arguments) {
            $arguments = \array_values($arguments);
        } else {
            $arguments = $this->arguments->getAll();
        }

        if (!$arguments) {
            return [];
        }

        $converter = $context->getConverter();

        $ret = [];
        foreach ($arguments as $index => $value) {
            $ret[] = $converter->toSQL($this->arguments->getTypeAt($index) ?? ConverterInterface::TYPE_UNKNOWN, $value, $context);
        }

        return $ret;
    }

    /**
     * Get raw SQL string ready to be sent to driver.
     */
    public function toString(): string
    {
        return $this->rawSQL;
    }

    /**
     * Allow transparent to string conversion.
     */
    public function __toString(): string
    {
        return $this->rawSQL;
    }
}
