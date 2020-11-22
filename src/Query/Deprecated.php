<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Expression\ColumnExpression;
use Goat\Query\Expression\ConstantRowExpression;
use Goat\Query\Expression\ConstantTableExpression;
use Goat\Query\Expression\IdentifierExpression;
use Goat\Query\Expression\LikeExpression;
use Goat\Query\Expression\RawExpression;
use Goat\Query\Expression\TableExpression;
use Goat\Query\Expression\ValueExpression;

/**
 * Deprecation helper.
 */
final class Deprecated
{
    /**
     * Create \trigger_error() arguments.
     */
    public static function error(string $deprecatedClass, string $newClass): array
    {
        return [
            \sprintf(
                "%s class is deprecated, please use %s class instead.",
                $deprecatedClass, $newClass
            ),
            E_USER_DEPRECATED
        ];
    }
}

/**
 * @deprecated
 * @see \Goat\Query\Expression\ConstantTableExpression
 */
final class ExpressionConstantTable extends ConstantTableExpression
{
    /**
     * @deprecated
     * @see \Goat\Query\Expression\ConstantTableExpression
     */
    public function __construct(?iterable $rows = null)
    {
        @\trigger_error(... Deprecated::error(__CLASS__, ConstantTableExpression::class));

        return parent::__construct($rows);
    }

    /**
     * @deprecated
     * @see \Goat\Query\Expression\ConstantTableExpression
     */
    public static function create(?iterable $rows = null): self
    {
        return new self($rows);
    }
}

/**
 * @deprecated
 * @see \Goat\Query\Expression\ConstantRowExpression
 */
final class ExpressionRow extends ConstantRowExpression
{
    /**
     * @deprecated
     * @see \Goat\Query\Expression\ConstantRowExpression
     */
    public function __construct(iterable $values)
    {
        @\trigger_error(... Deprecated::error(__CLASS__, ConstantRowExpression::class));

        return parent::__construct($values);
    }

    /**
     * @deprecated
     * @see \Goat\Query\Expression\ConstantRowExpression
     */
    public static function create(iterable $values): self
    {
        return new self($values);
    }
}

/**
 * @deprecated
 * @see \Goat\Query\Expression\LikeExpression
 * @see \Goat\Query\Where::isLike()
 * @see \Goat\Query\Where::isLikeInsensitive()
 * @see \Goat\Query\Where::isNoteLike()
 * @see \Goat\Query\Where::isNoteLikeInsensitive()
 */
final class ExpressionLike extends LikeExpression
{
}

/**
 * @deprecated
 * @see \Goat\Query\Expression\ColumnExpression
 */
final class ExpressionColumn extends ColumnExpression
{
    /**
     * @deprecated
     * @see \Goat\Query\Expression\ColumnExpression
     */
    public function __construct(string $columnName, ?string $tableAlias = null)
    {
        @\trigger_error(... Deprecated::error(__CLASS__, ColumnExpression::class));

        if (null === $tableAlias) {
            if (false !== \strpos($columnName, '.')) {
                list($tableAlias, $columnName) = \explode('.', $columnName, 2);
            }
        }

        parent::__construct($columnName, $tableAlias);
    }

    /**
     * @deprecated
     * @see \Goat\Query\Expression\ColumnExpression
     */
    public static function create(string $columnName, ?string $tableAlias = null): self
    {
        return new self($columnName, $tableAlias);
    }

    /**
     * @deprecated
     * @see \Goat\Query\Expression\ColumnExpression
     */
    public static function escape(string $columnName, ?string $tableAlias = null): self
    {
        return new self(new IdentifierExpression($columnName), $tableAlias);
    }

    /**
     * @deprecated
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
     * @see \Goat\Query\Expression\RawExpression
     */
    public function __construct(string $expression, $arguments = [])
    {
        @\trigger_error(... Deprecated::error(__CLASS__, RawExpression::class));

        parent::__construct($expression, $arguments);
    }

    /**
     * @deprecated
     * @see \Goat\Query\Expression\RawExpression
     */
    public static function create(string $expression, $arguments = []): self
    {
        return new self($expression, $arguments);
    }
}

/**
 * @deprecated
 * @see \Goat\Query\Expression\TableExpression
 */
final class ExpressionRelation extends TableExpression
{
    /**
     * @deprecated
     * @see \Goat\Query\Expression\TableExpression
     */
    public function __construct(string $name, ?string $alias = null, ?string $schema = null)
    {
        @\trigger_error(... Deprecated::error(__CLASS__, TableExpression::class));

        parent::__construct($name, $alias, $schema);
    }

    /**
     * @deprecated
     * @see \Goat\Query\Expression\TableExpression
     */
    public static function escape(string $name, ?string $alias = null, ?string $schema = null): self
    {
        return new self(new IdentifierExpression($name), $alias, $schema);
    }

    /**
     * @deprecated
     * @see \Goat\Query\Expression\TableExpression
     */
    public static function from($table): self
    {
        if ($table instanceof Expression) {
            @\trigger_error(... Deprecated::error(__CLASS__, TableExpression::class));

            return $table;
        }
        if (\is_string($table)) {
            return new self($table);
        }

        throw new QueryError(\sprintf("\$table argument must be a string or an instanceof of %s", __CLASS__));
    }

    /**
     * @deprecated
     * @see \Goat\Query\Expression\TableExpression
     */
    public static function create(string $name, ?string $alias = null, ?string $schema = null): self
    {
        return new self($name, $alias, $schema);
    }
}

/**
 * @deprecated
 * @see \Goat\Query\InsertQuery
 */
final class InsertQueryQuery extends InsertQuery
{
    /**
     * @deprecated
     * @see \Goat\Query\InsertQuery
     */
    public function __construct($table, ?string $alias = null)
    {
        @\trigger_error(... Deprecated::error(__CLASS__, InsertQuery::class));

        parent::__construct($table, $alias);
    }
}

/**
 * @deprecated
 * @see \Goat\Query\InsertQuery
 */
final class InsertValuesQuery extends InsertQuery
{
    /**
     * @deprecated
     * @see \Goat\Query\InsertQuery
     */
    public function __construct($table, ?string $alias = null)
    {
        @\trigger_error(... Deprecated::error(__CLASS__, InsertQuery::class));

        parent::__construct($table, $alias);
    }

    /**
     * @deprecated
     * @see \Goat\Query\InsertQuery
     */
    public function getValueCount(): int
    {
        $query = $this->getQuery();

        if ($query instanceof ConstantTableExpression) {
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
    /**
     * @deprecated
     * @see \Goat\Query\MergeQuery
     */
    public function __construct($table, ?string $alias = null)
    {
        @\trigger_error(... Deprecated::error(__CLASS__, MergeQuery::class));

        parent::__construct($table, $alias);
    }
}

/**
 * @deprecated
 * @see \Goat\Query\MergeQuery
 */
final class UpsertValuesQuery extends MergeQuery
{
    /**
     * @deprecated
     * @see \Goat\Query\MergeQuery
     */
    public function __construct($table, ?string $alias = null)
    {
        @\trigger_error(... Deprecated::error(__CLASS__, MergeQuery::class));

        parent::__construct($table, $alias);
    }

    /**
     * @deprecated
     * @see \Goat\Query\MergeQuery
     */
    public function getValueCount(): int
    {
        $query = $this->getQuery();

        if ($query instanceof ConstantTableExpression) {
            return $query->getRowCount();
        }

        return 0;
    }
}

/**
 * @deprecated
 * @see \Goat\Query\Expression\ValueExpression
 */
final class ExpressionValue extends ValueExpression
{
    /**
     * @deprecated
     * @see \Goat\Query\Expression\ValueExpression
     */
    public function __construct($value, ?string $type = null)
    {
        @\trigger_error(... Deprecated::error(__CLASS__, ValueExpression::class));

        parent::__construct($value, $type);
    }

    /**
     * @deprecated
     * @see \Goat\Query\Expression\ValueExpression
     */
    public static function create($value, ?string $type = null): self
    {
        return new self($value, $type);
    }
}

/**
 * @deprecated
 * @see \Goat\Query\Expression\ValueExpression
 */
final class Value extends ValueExpression
{
    private ?string $name = null;

    /**
     * @deprecated
     * @see \Goat\Query\Expression\ValueExpression
     */
    public function __construct($value, ?string $type = null, ?string $name = null)
    {
        @\trigger_error(... Deprecated::error(__CLASS__, ValueExpression::class));

        parent::__construct($value, $type);

        $this->name = $name;
    }

    /**
     * @deprecated
     * @see \Goat\Query\Expression\ValueExpression
     */
    public static function create($value, ?string $type = null, ?string $name = null): self
    {
        return new self($value, $type, $name);
    }

    /**
     * @deprecated
     * @see \Goat\Query\Expression\ValueExpression
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}
