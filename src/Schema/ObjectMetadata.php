<?php

declare(strict_types=1);

namespace Goat\Schema;

/**
 * Database object metadata.
 */
interface ObjectMetadata extends NamedMetadata
{
    const OBJECT_TYPE_COLUMN = 'column';
    const OBJECT_TYPE_CONSTRAINT = 'constraint';
    const OBJECT_TYPE_FOREIGN_KEY = 'foreignkey';
    const OBJECT_TYPE_KEY = 'key';
    const OBJECT_TYPE_PRIMARY_KEY = 'primarykey';
    const OBJECT_TYPE_SEQUENCE = 'sequence';
    const OBJECT_TYPE_TABLE = 'table';
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
}
