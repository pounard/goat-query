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

    protected function __construct(string $name, ?string $alias = null, ?string $schema = null)
    {
        $this->alias = $alias;
        $this->name = $name;
        $this->schema = $schema;
    }

    /**
     * Creates an instance without automatic split using '.' notation.
     */
    public static function escape(string $name, ?string $alias = null, ?string $schema = null): self
    {
        return new self($name, $alias, $schema);
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
