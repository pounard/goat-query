<?php

namespace Goat\Converter\Driver;

use Goat\Converter\ConverterContext;
use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;

/**
 * MySQL 5.x converter, should work for 8.x as well.
 */
class MySQLConverter extends DefaultConverter
{
    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value, ConverterContext $context): ?string
    {
        if (ConverterInterface::TYPE_UNKNOWN === $type) {
            $type = $this->guessType($value, $context);
        }

        switch ($type) {
            // MySQL does not have a boolean native type.
            case 'bool':
            case 'boolean':
                return $value ? '1' : '0';

            default:
                return parent::toSQL($type, $value, $context);
        }
    }
}
