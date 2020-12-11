<?php

declare(strict_types=1);

namespace Goat\Schema\Browser;

use Goat\Schema\KeyMetatadata;

/**
 * Schema browsing context brings a few utilities to know where you are:
 *
 *  - depth is informational, it arbitrarily gives you the recursion level,
 *    depth starts at 1, being the first schema being browsed, tables are 2,
 *    then it inscreases to 3 for each relation, 4 for related tables, etc... 
 *  - schema and table gives you the root branch you are currently browsing,
 *  - circular dependency helpers may help you breaking circular dependencies.
 *
 * Also remember that while browsing relations, you could go throught one
 * schema to another, since schema are no more than namespaces and tables from
 * some namespace can reference tables from another one.
 *
 * Throught the given interface, modifier methods are not exposed, it's the
 * main browser object's role to do so.
 */
interface Context
{
    public function getBrowserMode(): int;

    public function getDepth(): int;

    public function getRootSchema(): string;

    public function getRootTable(): string;

    public function hasAlreadyBrowsed(KeyMetatadata $key): bool;
}
