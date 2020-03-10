<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Driver\Query\DefaultSqlWriter;

class FooSqlWriter extends DefaultSqlWriter
{
    /**
     * {@inheritdoc}
     */
    protected function writeCast(string $placeholder, string $type): string
    {
        // This is supposedly SQL-92 standard compliant, but can be overriden
        return \sprintf("cast(%s as %s)", $placeholder, $type);
    }
}
