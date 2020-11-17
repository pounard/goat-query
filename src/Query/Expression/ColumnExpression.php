<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\ArgumentBag;
use Goat\Query\Expression;

/**
 * Represents a table column identifier.
 */
class ColumnExpression implements Expression
{
    private string $columnName;
    private ?string $tableAlias = null;

    protected function __construct(string $columnName, ?string $tableAlias = null)
    {
        $this->columnName = $columnName;
        $this->tableAlias = $tableAlias;
    }

    /**
     * Create instance from name and alias.
     */
    public static function create(string $columnName, ?string $tableAlias = null): self
    {
        if (null === $tableAlias) {
            if (false !== \strpos($columnName, '.')) {
                list($tableAlias, $columnName) = \explode('.', $columnName, 2);
            }
        }

        return new self($columnName, $tableAlias);
    }

    /**
     * Creates an instance without automatic split using '.' notation.
     */
    public static function escape(string $columnName, ?string $tableAlias = null): self
    {
        return new self($columnName, $tableAlias);
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
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        return new ArgumentBag();
    }
}
