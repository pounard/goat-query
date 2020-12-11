<?php

declare(strict_types=1);

namespace Goat\Schema;

/**
 * Database object metadata.
 */
interface ObjectMetadata extends NamedMetadata
{
    const OBJECT_TYPE_COLUMN = 'col';
    const OBJECT_TYPE_CONSTRAINT = 'con';
    const OBJECT_TYPE_FOREIGN_KEY = 'f';
    const OBJECT_TYPE_KEY = 'k';
    const OBJECT_TYPE_PRIMARY_KEY = 'p';
    const OBJECT_TYPE_SEQUENCE = 'seq';
    const OBJECT_TYPE_TABLE = 'rel';
    const OBJECT_TYPE_VIEW = 'view';

    /**
     * Get object type, one of the self::OBJECT_TYPE_* constant.
     */
    public function getObjectType(): string;

    /**
     * Get database name this table is into.
     */
    public function getDatabase(): string;

    /**
     * Get schema this table is into.
     */
    public function getSchema(): string;

    /**
     * Tell if both instance targets the same object.
     */
    public function equals(ObjectMetadata $other): bool;

    /**
     * Get a unique identifier hash.
     */
    public function getObjectHash(): string;
}
