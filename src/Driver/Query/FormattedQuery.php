<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

use Goat\Converter\ConverterInterface;

/**
 * Carries a formatted query for a driver, along with its arguments types
 */
final class FormattedQuery
{
    private ?string $identifier;
    private string $rawSQL;
    private ?ArgumentBag $arguments = null;
    /** array<int,null|string> */
    private array $types;

    /**
     * Default constructor
     */
    public function __construct(
        string $rawSQL,
        array $types,
        ?string $identifier = null,
        ?ArgumentBag $arguments = null
    ) {
        $this->types = $types;
        $this->identifier = $identifier;
        $this->rawSQL = $rawSQL;
        $this->arguments = $arguments ?? new ArgumentBag();
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
        return $this->types;
    }

    /**
     * Get formatted and escaped SQL query with placeholders.
     */
    public function getRawSQL(): string
    {
        return $this->rawSQL;
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
    public function prepareArgumentsWith(ConverterInterface $converter, array $arguments = null): array
    {
        // If null was given, this means that we should use query given
        // arguments, those who are already set in this object.
        if (!$arguments) {
            $arguments = $this->arguments->getAll();
        } else {
            $arguments = \array_values($arguments);
        }

        if (!$arguments) {
            return [];
        }

        $ret = [];
        foreach ($arguments as $index => $value) {
            $type = $this->types[$index] ?? $this->arguments->getTypeAt($index) ?? ConverterInterface::TYPE_UNKNOWN;

            $ret[] = $converter->toSQL($type, $value);
        }

        return $ret;
    }
}
