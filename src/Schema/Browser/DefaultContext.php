<?php

declare(strict_types=1);

namespace Goat\Schema\Browser;

use Goat\Schema\ObjectMetadata;

/**
 * Schema browsing context brings a few utilities to know where you are:
 *
 *  - depth is informational, it arbitrarily gives you the recursion level,
 *  - schema and table gives you the root branch you are currently browsing,
 *  - circular dependency helpers may help you breaking circular dependencies.
 *
 * Throught the given interface, modifier methods are not exposed, it's the
 * main browser object's role to do so.
 */
final class DefaultContext implements Context
{
    private int $browserMode;
    private int $depth = 0;
    private string $schema;
    private string $table;

    /**
     * Circular dependency browse breaker.
     *
     * This is the single and only piece of code in the whole schema browsing
     * API that is not scalable and will progressively eat memory and never
     * release it.
     *
     * @todo
     *   Find a way to make this more scalable by storing less data.
     */
    private array $broswed = [];

    public function __construct(int $browserMode, string $schema, string $table)
    {
        $this->browserMode = $browserMode;
        $this->switch($schema, $table);
    }

    public function getBrowserMode(): int
    {
        return $this->browserMode;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getRootSchema(): string
    {
        return $this->schema;
    }

    public function getRootTable(): string
    {
        return $this->table;
    }

    public function markAsBroswed(ObjectMetadata $object): void
    {
        $this->broswed[$object->getObjectHash()] = true;
    }

    public function hasAlreadyBrowsed(ObjectMetadata $object): bool
    {
        return isset($this->broswed[$object->getObjectHash()]);
    }

    public function switch(string $schema, string $table)
    {
        $this->table = $table;
        $this->schema = $schema;
    }

    public function enter(): void
    {
        $this->depth++;
    }

    public function leave(): void
    {
        $this->depth--;
    }
}
