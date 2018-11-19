<?php

declare(strict_types=1);

namespace Goat\Query\Impl;

use Goat\Query\Writer\DefaultFormatter;

/**
 * PostgreSQL >= 8.4 (untested before, althought it might work)
 */
class PgSQLFormatter extends DefaultFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function writeCast(string $placeholder, string $type): string
    {
        // No surprises there, PostgreSQL is very straight-forward and just
        // uses the datatypes as it handles it. Very stable and robust.
        return \sprintf("%s::%s", $placeholder, $type);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatInsertNoValuesStatement(): string
    {
        return "DEFAULT VALUES";
    }
}
