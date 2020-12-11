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
    private ?string $hash = null;

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
        if ($other === $this) {
            return true;
        }
        if ($this->getObjectHash() !== $other->getObjectHash()) {
            return false;
        }

        // If hash is the same, ensure there was no colisions in hashes.
        // @todo It's actually possible to have different objects having the
        //   same name, for example keys in different tables.
        return
            $other->getObjectType() === $this->objectType &&
            $other->getName() === $this->name  &&
            $other->getSchema() === $this->schema &&
            $other->getDatabase() === $this->database
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectHash(): string
    {
        return $this->hash ?? ($this->hash = $this->computeObjectHash());
    }

    private function computeObjectHash(): string
    {
        // Collisitions are possible, even thought there's not probable.
        return \sha1($this->database . ':' . $this->schema . ':' . $this->objectType . ':' . $this->name);
    }
}
