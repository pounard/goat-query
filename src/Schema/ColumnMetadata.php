<?php

declare(strict_types=1);

namespace Goat\Schema;

/**
 * Column metadata.
 *
 * You wont be able to fetch default value, default values can be complex
 * SQL expressions or function calls, even DBMS constant values or aliases.
 */
interface ColumnMetadata extends ObjectMetadata
{
    /**
     * Get value type.
     */
    public function getType(): string;

    /**
     * Get value type.
     */
    public function getTable(): string;

    /**
     * Is this value nullable
     */
    public function isNullable(): bool;

    /**
     * Get column collation if any
     */
    public function getCollation(): ?string;

    /**
     * Length if applicable (text...)
     */
    public function getLength(): ?int;

    /**
     * Precision if applicable (numbers...)
     */
    public function getPrecision(): ?int;

    /**
     * Scale if applicable (numbers...)
     */
    public function getScale(): ?int;

    /**
     * Is unsigned if applicable, otherwise always false
     */
    public function isUnsigned(): bool;

    /**
     * Is column using a sequence to auto populate
     */
    public function isSequence(): bool;
}
