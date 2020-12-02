<?php

declare(strict_types=1);

namespace Goat\Schema;

/**
 * Named element in the database.
 */
interface NamedMetadata
{
    /**
     * Get name.
     */
    public function getName(): string;

    /**
     * Get comment.
     */
    public function getComment(): ?string;
}
