<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Represents a raw value
 */
final class ExpressionRelation implements Expression
{
    private $alias;
    private $relation;
    private $schema;

    /**
     * Default constructor
     */
    private function __construct()
    {
    }

    /**
     * Creates an instance without automatic split using '.' notation
     */
    public static function escape(string $name, ?string $alias = null, ?string $schema = null) : self
    {
        $ret = new self;
        $ret->alias = $alias;
        $ret->relation = $name;
        $ret->schema = $schema;

        return $ret;
    }

    /**
     * Create instance from arbitrary input value
     */
    public static function from($relation): self
    {
        if (!$relation instanceof ExpressionRelation) {
            $relation = self::create($relation);
        }

        return $relation;
    }

    /**
     * Create instance from name and alias
     */
    public static function create(string $name, ?string $alias = null, ?string $schema = null): self
    {
        if (null === $schema) {
            if (false !== \strpos($name, '.')) {
                list($schema, $name) = \explode('.', $name, 2);
            }
        }

        return self::escape($name, $alias, $schema);
    }

    /**
     * Get relation
     */
    public function getName(): string
    {
        return $this->relation;
    }

    /**
     * Get alias
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Get schema
     */
    public function getSchema(): ?string
    {
        return $this->schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        return new ArgumentBag();
    }
}
