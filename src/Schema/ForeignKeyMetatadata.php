<?php

declare(strict_types=1);

namespace Goat\Schema;

interface ForeignKeyMetatadata extends KeyMetatadata
{
    /**
     * Get table schema this foreign key references.
     */
    public function getForeignSchema(): string;

    /**
     * Get table this foreign key references.
     */
    public function getForeignTable(): string;

    /**
     * Get ordered column names this foreign key references.
     */
    public function getForeignColumnNames(): array;
}
