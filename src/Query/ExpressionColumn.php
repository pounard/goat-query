<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Represents a table column identifier.
 */
final class ExpressionColumn implements Expression
{
    private string $columnName;
    private ?string $tableAlias = null;

    /**
     * @deprecated
     *   Use static create() method instead.
     */
    public function __construct(string $columnName, ?string $tableAlias = null)
    {
        if (null === $tableAlias) {
            if (false !== \strpos($columnName, '.')) {
                list($tableAlias, $columnName) = \explode('.', $columnName, 2);
            }
        }

        $this->columnName = $columnName;
        $this->tableAlias = $tableAlias;
    }

    /**
     * Create instance from name and alias.
     */
    public static function create(string $columnName, ?string $tableAlias = null): self
    {
        return new self($columnName, $tableAlias);
    }

    /**
     * Creates an instance without automatic split using '.' notation.
     */
    public static function escape(string $columnName, ?string $tableAlias = null): self
    {
        $instance = self::create('');
        $instance->columnName = $columnName;
        $instance->tableAlias = $tableAlias;

        return $instance;
    }

    /**
     * Get column name.
     */
    public function getName(): string
    {
        return $this->columnName;
    }

    /**
     * Get table alias.
     */
    public function getTableAlias(): ?string
    {
        return $this->tableAlias;
    }

    /**
     * @deprected
     * @see \Goat\Query\ExpressionColumn::getTableAlias()
     */
    public function getRelationAlias(): ?string
    {
        return $this->tableAlias;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        return new ArgumentBag();
    }
}
