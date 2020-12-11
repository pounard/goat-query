<?php

declare(strict_types=1);

namespace Goat\Schema\Implementation;

use Goat\Schema\ColumnMetadata;
use Goat\Schema\ObjectMetadata;

class DefaultColumnMetadata implements ColumnMetadata
{
    use ObjectMetadataTrait;

    private string $type;
    private string $table;
    private bool $nullabe = false;
    private ?string $collation = null;
    private ?int $length = null;
    private ?int $precision = null;
    private ?int $scale = null;
    private bool $unsigned = false;
    private bool $sequence = false;

    public function __construct(
        string $database,
        string $schema,
        string $table,
        string $name,
        ?string $comment,
        string $type,
        bool $nullabe,
        ?string $collation = null,
        ?int $length = null,
        ?int $precision = null,
        ?int $scale = null,
        bool $unsigned = false,
        bool $sequence = false
    ) {
        $this->collation = $collation;
        $this->comment = $comment;
        $this->database = $database;
        $this->length = $length;
        $this->name = $name;
        $this->nullabe = $nullabe;
        $this->objectType = ObjectMetadata::OBJECT_TYPE_COLUMN;
        $this->precision = $precision;
        $this->scale = $scale;
        $this->schema = $schema;
        $this->sequence = $sequence;
        $this->table = $table;
        $this->type = $type;
        $this->unsigned = $unsigned;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->type;
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
    public function isNullable(): bool
    {
        return $this->nullabe;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollation(): ?string
    {
        return $this->collation;
    }

    /**
     * {@inheritdoc}
     */
    public function getLength(): ?int
    {
        return $this->length;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * {@inheritdoc}
     */
    public function getScale(): ?int
    {
        return $this->scale;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * {@inheritdoc}
     */
    public function isSequence(): bool
    {
        return $this->sequence;
    }
}
