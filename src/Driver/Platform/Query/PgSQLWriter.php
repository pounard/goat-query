<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Query;

use Goat\Driver\Query\DefaultSqlWriter;

/**
 * PostgreSQL >= 8.4 (untested before, althought it might work)
 */
class PgSQLWriter extends DefaultSqlWriter
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
