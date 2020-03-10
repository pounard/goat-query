<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Converter\Driver\PgSQLConverter;

class PDOPgSQLRunner extends AbstractPDORunner
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return 'pgsql';
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter): void
    {
        parent::setConverter(new PgSQLConverter($converter));
    }
}
