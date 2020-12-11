<?php

declare(strict_types=1);

namespace Goat\Schema\Implementation;

trait ObjectMetadataTrait /* implements ObjectMetadata */
{
    use NamedMetadataTrait;

    private string $objectType;
    private string $database;
    private string $schema;

    /**
     * {@inheritdoc}
     */
    public function getObjectType(): string
    {
        return $this->objectType;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema(): string
    {
        return $this->schema;
    }
}
