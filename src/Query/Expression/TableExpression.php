<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\ArgumentBag;
use Goat\Query\Expression;
use Goat\Query\QueryError;
use Goat\Query\Partial\WithAlias;
use Goat\Query\Partial\WithAliasTrait;

class TableExpression implements Expression, WithAlias
{
    use WithAliasTrait;

    private string $name;
    private ?string $schema = null;

    private function __construct()
    {
    }

    /**
     * Creates an instance without automatic split using '.' notation.
     */
    public static function escape(string $name, ?string $alias = null, ?string $schema = null): self
    {
        $ret = new self;
        $ret->alias = $alias;
        $ret->name = $name;
        $ret->schema = $schema;

        return $ret;
    }

    /**
     * Create instance from arbitrary input value.
     */
    public static function from($table): self
    {
        if (\is_string($table)) {
            return self::create($table);
        }
        if ($table instanceof self) {
            return $table;
        }

        throw new QueryError(\sprintf("\$table argument must be a string or an instanceof of %s", __CLASS__));
    }

    /**
     * Create instance from name and alias.
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
     * Get table name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get schema.
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
