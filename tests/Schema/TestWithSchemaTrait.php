<?php

declare(strict_types=1);

namespace Goat\Schema\Tests;

use Goat\Runner\Runner;

trait TestWithSchemaTrait
{
    protected function createInitialSchema(Runner $runner): void
    {
        $driver = $runner->getDriverName();

        if (false !== \strpos($driver, 'pgsql')) {
            $this->createInitialSchemaFromFile($runner, __DIR__ . '/Schema/schema.pgsql.sql');
        }

        if (false !== \strpos($driver, 'mysql')) {
            self::markTestSkipped("Schema introspector is not implemented in MySQL yet.");
        }
    }

    private function createInitialSchemaFromFile(Runner $runner, string $filename): void
    {
        $statements = \array_filter(
            \array_map(
                // Do not execute void (empty lines).
                fn ($text) => \trim($text),
                // Hopefully, we don't have any dangling ';' in escaped text.
                \explode(
                    ';',
                    \file_get_contents($filename)
                )
            )
        );

        foreach ($statements as $statement) {
            $runner->execute($statement);
        }
    }
}
