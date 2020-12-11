<?php

declare(strict_types=1);

namespace Goat\Schema\Browser;

use Goat\Schema\KeyMetatadata;

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
    private array $browed = [];

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

    public function markAsBrowed(KeyMetatadata $key): void
    {
        $this->browed[$this->compteKeyHash($key)] = true;
    }

    public function hasAlreadyBrowsed(KeyMetatadata $key): bool
    {
        return isset($this->browed[$this->compteKeyHash($key)]);
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

    private function compteKeyHash(KeyMetatadata $key): string
    {
        return $key->getDatabase() . ':' . $key->getSchema() . ':' . $key->getTable() . ':' . $key->getName();
    }
}
