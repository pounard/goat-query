<?php

declare(strict_types=1);

namespace Goat\Schema\Implementation;

use Goat\Schema\ObjectMetadata;

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

    /**
     * {@inheritdoc}
     */
    public function equals(ObjectMetadata $other): bool
    {
        return $other === $this || (
            $other->getObjectType() === $this->objectType &&
            $other->getName() === $this->name  &&
            $other->getSchema() === $this->schema &&
            $other->getDatabase() === $this->database
        );
    }
}
