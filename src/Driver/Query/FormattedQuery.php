<?php

declare(strict_types=1);

namespace Goat\Driver\Query;

use Goat\Converter\ConverterInterface;
use Goat\Query\ArgumentList;
use Goat\Query\QueryError;
use Goat\Query\Statement;

/**
 * Carries a formatted query for a driver, along with its arguments types
 */
final class FormattedQuery
{
    private $argumentList;
    private $identifier;
    private $rawSQL;

    /**
     * Default constructor
     */
    public function __construct(string $rawSQL, ArgumentList $argumentList, ?string $identifier = null)
    {
        $this->argumentList = $argumentList;
        $this->identifier = $identifier;
        $this->rawSQL = $rawSQL;
    }

    /**
     * Get argument count
     */
    public function getArgumentList(): ArgumentList
    {
        return $this->argumentList;
    }

    /**
     * Get formatted and escaped SQL query with placeholders
     */
    public function getRawSQL(): string
    {
        return $this->rawSQL;
    }

    /**
     * Get query identifier
     */
    public function getIdentifier(): string
    {
        return $this->identifier ?? ($this->identifier = 'gqi_'.\md5($this->rawSQL));
    }

    /**
     * Prepare arguments.
     *
     * ArgumentList comes from the SQL formatter, and gives us information
     * about the number of argument and their types if found. Based upon this
     * information, it will:
     *
     *   - either just convert given arguments if it's a bare array,
     *   - merge type information then convert if it's an ArgumentBag.
     *
     * In all cases, it will reconcile the awaited parameter count and raise
     * errors if the number doesn't match.
     */
    private function convertArguments(ConverterInterface $converter, ArgumentList $argumentList, array $arguments): array
    {
        $ret = [];

        $input = [];
        foreach ($arguments as $index => $value) {
            if (\is_int($index)) {
                $input[$index] = $value;
            } else {
                $input[$argumentList->getNameIndex($index)] = $value;
            }
        }
        $types = $argumentList->getTypeMap();
        $count = $argumentList->count();

        if (\count($input) !== $count) {
            throw new QueryError(\sprintf("Invalid parameter number bound"));
        }

        for ($i = 0; $i < $count; ++$i) {
            $ret[$i] = $converter->toSQL($types[$i] ?? ConverterInterface::TYPE_UNKNOWN, $input[$i]);
        }

        return $ret;
    }

    /**
     * Prepare arguments with the given input
     *
     * This method should belong to the driver namespace, but there is no way
     * to make this very explicit and clear and decoupled from a specific driver
     * implementation at the same time. This should be the only converter
     * namespace dependency in the query namespace.
     */
    public function prepareArgumentsWith(ConverterInterface $converter, $query, $arguments = null): array
    {
        $argumentList = $this->argumentList;

        if ($argumentList->count()) {
            if ($query instanceof Statement) {
                $queryArguments = $query->getArguments();
                $argumentList = $argumentList->withTypesOf($queryArguments);
                if (!$arguments) {
                    $arguments = $queryArguments->getAll();
                }
            }
            return $this->convertArguments($converter, $argumentList, $arguments);
        }

        return [];
    }
}
