<?php

declare(strict_types=1);

namespace Goat\Schema\Implementation;

use Goat\Schema\ForeignKeyMetatadata;
use Goat\Schema\ObjectMetadata;

final class DefaultForeignKeyMetatadata implements ForeignKeyMetatadata
{
    use ObjectMetadataTrait;

    private string $table;
    private array $columnNames;
    private string $foreignTable;
    private string $foreignSchema;
    private array $foreignColumnNames;

    public function __construct(
        string $database,
        string $schema,
        string $table,
        string $name,
        ?string $comment,
        array $columnNames,
        string $foreignSchema,
        string $foreignTable,
        array $foreignColumnNames
    ) {
        $this->columnNames = $columnNames;
        $this->comment = $comment;
        $this->database = $database;
        $this->foreignColumnNames = $foreignColumnNames;
        $this->foreignSchema = $foreignSchema;
        $this->foreignTable = $foreignTable;
        $this->name = $name;
        $this->objectType = ObjectMetadata::OBJECT_TYPE_FOREIGN_KEY;
        $this->schema = $schema;
        $this->table = $table;
    }

    /**
     * {@inheritdoc}
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignSchema(): string
    {
        return $this->foreignSchema;
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignColumnNames(): array
    {
        return $this->foreignColumnNames;
    }
}
