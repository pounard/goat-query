<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Expression\ColumnExpression;
use Goat\Query\Expression\RawExpression;
use Goat\Query\Expression\TableExpression;

/**
 * @deprecated
 * @see \Goat\Query\Expression\ColumnExpression::create()
 */
final class ExpressionColumn extends ColumnExpression
{
    /**
     * @deprecated
     * @see \Goat\Query\Expression\ColumnExpression::create()
     */
    public function __construct(string $columnName, ?string $tableAlias = null)
    {
        if (null === $tableAlias) {
            if (false !== \strpos($columnName, '.')) {
                list($tableAlias, $columnName) = \explode('.', $columnName, 2);
            }
        }

        parent::__construct($columnName, $tableAlias);
    }

    /**
     * @deprected
     * @see \Goat\Query\Expression\ColumnExpression::getTableAlias()
     */
    public function getRelationAlias(): ?string
    {
        return $this->tableAlias;
    }
}

/**
 * @deprecated
 * @see \Goat\Query\Expression\RawExpression
 */
final class ExpressionRaw extends RawExpression
{
    /**
     * @deprecated
     * @see \Goat\Query\Expression\RawExpression::create()
     */
    public function __construct(string $expression, $arguments = [])
    {
        parent::__construct($expression, $arguments);
    }
}

/**
 * @deprecated
 * @see \Goat\Query\Expression\TableExpression
 */
final class ExpressionRelation extends TableExpression
{
}

/**
 * @deprecated
 * @see \Goat\Query\InsertQuery
 */
final class InsertQueryQuery extends InsertQuery
{
}

/**
 * @deprecated
 * @see \Goat\Query\InsertQuery
 */
final class InsertValuesQuery extends InsertQuery
{
    public function getValueCount(): int
    {
        $query = $this->getQuery();

        if ($query instanceof ExpressionConstantTable) {
            return $query->getRowCount();
        }

        return 0;
    }
}

/**
 * @deprecated
 * @see \Goat\Query\MergeQuery
 */
final class UpsertQueryQuery extends MergeQuery
{
}

/**
 * @deprecated
 * @see \Goat\Query\MergeQuery
 */
final class UpsertValuesQuery extends MergeQuery
{
    public function getValueCount(): int
    {
        $query = $this->getQuery();

        if ($query instanceof ExpressionConstantTable) {
            return $query->getRowCount();
        }

        return 0;
    }
}

/**
 * @deprecated
 */
final class Value implements ValueRepresentation
{
    private $name;
    private $type;
    private $value;

    /**
     * Build from value
     */
    public function __construct($value, ?string $type = null, ?string $name = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}
