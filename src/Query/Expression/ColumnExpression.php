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

    /**
     * @param string|IdentifierExpression $columnName
     */
    public function __construct($columnName, ?string $tableAlias = null)
    {
        if (null === $tableAlias) {
            if ($columnName instanceof IdentifierExpression) {
                $columnName = $columnName->getName();
            } else if (false !== \strpos($columnName, '.')) {
                list($tableAlias, $columnName) = \explode('.', $columnName, 2);
            }
        }

        $this->columnName = $columnName;
        $this->tableAlias = $tableAlias;
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
