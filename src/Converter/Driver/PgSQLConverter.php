<?php

namespace Goat\Converter\Driver;

use Goat\Converter\DefaultConverter;
use Goat\Converter\ValueConverterRegistry;

/**
 * PostgreSQL all versions converter.
 */
class PgSQLConverter extends DefaultConverter
{
    /**
     * {@inheritdoc}
     */
    public function setValueConverterRegistry(ValueConverterRegistry $valueConverterRegistry): void
    {
        $valueConverterRegistry->register(new PgSQLArrayConverter());

        parent::setValueConverterRegistry($valueConverterRegistry);
    }
}
