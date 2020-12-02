<?php

declare(strict_types=1);

namespace Goat\Schema;

class DefaultTableMetadata implements TableMetadata
{
    use ObjectMetadataTrait;

    /** @var string[] */
    private ?array $primaryKey;
    /** @var array<string, ColumnMetadata> */
    private array $columns;
    /** @var array<string, string> */
    private array $columnTypeMap = [];

    public function __construct(
        string $database,
        string $schema,
        string $name,
        ?string $comment,
        ?array $primaryKey,
        array $columns
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
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKey(): ?array
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
