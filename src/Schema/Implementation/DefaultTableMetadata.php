<?php

declare(strict_types=1);

namespace Goat\Schema\Implementation;

use Goat\Schema\ColumnMetadata;
use Goat\Schema\ForeignKeyMetatadata;
use Goat\Schema\ObjectMetadata;
use Goat\Schema\TableMetadata;
use Goat\Schema\KeyMetatadata;

class DefaultTableMetadata implements TableMetadata
{
    use ObjectMetadataTrait;

    /** @var string[] */
    private ?KeyMetatadata $primaryKey;
    /** @var array<string, ColumnMetadata> */
    private array $columns;
    /** @var array<string, string> */
    private array $columnTypeMap = [];

    /** @var ?callable */
    private $fetchForeignKeys = null;
    private ?array $foreignKeys = null;
    /** @var ?callable */
    private $fetchReverseForeignKeys = null;
    private ?array $reverseForeignKeys = null;

    /**
     * @param callable|ForeignKeyMetatadata[] $foreignKeys
     * @param callable|ForeignKeyMetatadata[] $reverseForeignKeys
     */
    public function __construct(
        string $database,
        string $schema,
        string $name,
        ?string $comment,
        ?KeyMetatadata $primaryKey,
        array $columns,
        $foreignKeys,
        $reverseForeignKeys
    ) {
        $this->columns = $columns;
        $this->comment = $comment;
        $this->database = $database;
        $this->name = $name;
        $this->objectType = ObjectMetadata::OBJECT_TYPE_TABLE;
        $this->primaryKey = $primaryKey;
        $this->schema = $schema;

        foreach ($columns as $name => $column) {
            \assert($column instanceof ColumnMetadata);
            $this->columnTypeMap[$name] = $column->getType();
        }

        if (\is_callable($foreignKeys)) {
            $this->fetchForeignKeys = $this->fetchForeignKeys;
        } else if (\is_array($foreignKeys)) {
            $this->foreignKeys = $foreignKeys;
        } else {
            throw new \InvalidArgumentException(\sprintf("\$foreignKeys must be a callable or an array of %s instances.", ForeignKeyMetatadata::class));
        }

        if (\is_callable($reverseForeignKeys)) {
            $this->fetchReverseForeignKeys = $reverseForeignKeys;
        } else if (\is_array($reverseForeignKeys)) {
            $this->reverseForeignKeys = $reverseForeignKeys;
        } else {
            throw new \InvalidArgumentException(\sprintf("\$reverseForeignKeys must be a callable or an array of %s instances.", ForeignKeyMetatadata::class));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKey(): ?KeyMetatadata
    {
        return $this->primaryKey;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPrimaryKey(): bool
    {
        return !empty($this->primaryKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignKeys(): array
    {
        if (null !== $this->foreignKeys) {
            return $this->foreignKeys;
        }

        if ($this->fetchForeignKeys) {
            $this->foreignKeys = ($this->fetchForeignKeys)() ?? [];
            $this->fetchForeignKeys = null;
        }

        return $this->foreignKeys ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getReverseForeignKeys(): array
    {
        if (null !== $this->reverseForeignKeys) {
            return $this->reverseForeignKeys;
        }

        if ($this->fetchReverseForeignKeys) {
            $this->reverseForeignKeys = ($this->fetchReverseForeignKeys)() ?? [];
            $this->reverseForeignKeys = null;
        }

        return $this->reverseForeignKeys ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnTypeMap(): array
    {
        return $this->columnTypeMap;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
