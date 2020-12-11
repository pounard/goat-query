<?php

declare(strict_types=1);

namespace Goat\Schema\Implementation;

use Goat\Schema\KeyMetatadata;
use Goat\Schema\ObjectMetadata;

final class DefaultKeyMetatadata implements KeyMetatadata
{
    use ObjectMetadataTrait;

    private string $table;
    private array $columnNames;

    public function __construct(
        string $database,
        string $schema,
        string $table,
        string $name,
        ?string $comment,
        array $columnNames
    ) {
        $this->columnNames = $columnNames;
        $this->comment = $comment;
        $this->database = $database;
        $this->name = $name;
        $this->objectType = ObjectMetadata::OBJECT_TYPE_PRIMARY_KEY;
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
}
