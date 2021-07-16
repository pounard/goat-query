<?php

namespace Goat\Converter\Driver;

use Goat\Converter\Converter;
use Goat\Driver\Runner\RunnerConverter;

/**
 * PostgreSQL all versions converter.
 */
class PgSQLConverter extends RunnerConverter 
{
    /**
     * {@inheritdoc}
     */
    protected function initialize(Converter $decorated): void
    {
        $decorated->register(new PgSQLArrayConverter());
        $decorated->register(new PgSQLRowConverter());
    }
}
