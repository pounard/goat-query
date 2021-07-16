<?php

namespace Goat\Converter\Driver;

use Goat\Converter\ConverterContext;
use Goat\Driver\Runner\RunnerConverter;

/**
 * MySQL 5.x converter, should work for 8.x as well.
 */
class MySQLConverter extends RunnerConverter
{
    /**
     * {@inheritdoc}
     */
    public function toSQL(/* mixed */ $value, ?string $sqlType, ?ConverterContext $context = null): ?string
    {
        switch ($sqlType) {
            // MySQL does not have a boolean native type.
            case 'bool':
            case 'boolean':
                return $value ? '1' : '0';

            default:
                return parent::toSQL($value, $sqlType, $context);
        }
    }
}
