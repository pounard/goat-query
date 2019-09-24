<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Represents a raw value
 */
final class ExpressionColumn implements Expression
{
    private $columnName;
    private $relationAlias;

    /**
     * Default constructor
     *
     * @param string $columnName
     * @param string $relationAlias
     *
     * @deprecated
     *   Use static create() method instead.
     */
    public function __construct(string $columnName, string $relationAlias = null)
    {
        if (null === $relationAlias) {
            if (false !== \strpos($columnName, '.')) {
                list($relationAlias, $columnName) = \explode('.', $columnName, 2);
            }
        }

        $this->columnName = $columnName;
        $this->relationAlias = $relationAlias;
    }

    /**
     * Create instance from name and alias
     */
    public static function create(string $columnName, string $relationAlias = null): self
    {
        return new self($columnName, $relationAlias);
    }

    /**
     * Creates an instance without automatic split using '.' notation
     *
     * @param string $relationName
     * @param string $alias
     * @param string $schema
     *
     * @return ExpressionRelation
     */
    public static function escape(string $columnName, string $relationAlias = null) : ExpressionColumn
    {
        $instance = self::create('');
        $instance->columnName = $columnName;
        $instance->relationAlias = $relationAlias;

        return $instance;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->columnName;
    }

    /**
     * Get value type
     *
     * @return null|string
     */
    public function getRelationAlias()
    {
        return $this->relationAlias;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments() : ArgumentBag
    {
        return new ArgumentBag();
    }
}
