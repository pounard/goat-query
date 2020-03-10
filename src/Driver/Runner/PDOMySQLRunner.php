<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Converter\Driver\MySQLConverter;

class PDOMySQLRunner extends AbstractPDORunner
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return 'mysql';
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter): void
    {
        parent::setConverter(new MySQLConverter($converter));
    }
}
