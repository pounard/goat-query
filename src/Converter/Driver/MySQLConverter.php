<?php

namespace Goat\Converter\Driver;

use Goat\Converter\ConverterInterface;

/**
 * MySQL 5.x converter, should work for 8.x as well.
 *
 * @todo move this into the Goat\Runner\Driver namespace
 */
class MySQLConverter implements ConverterInterface
{
    private $default;

    /**
     * Default constructor
     */
    public function __construct(ConverterInterface $default)
    {
        $this->default = $default;
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value)
    {
        return $this->default->fromSQL($type, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($value): string
    {
        return $this->default->guessType($value);
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value): ?string
    {
        if (ConverterInterface::TYPE_UNKNOWN === $type) {
            $type = $this->guessType($value);
        }

        switch ($type) {

            case 'bool':
            case 'boolean':
                // MySQL does not have a boolean native type
                return $value ? '1' : '0';
        }

        return $this->default->toSQL($type, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function needsCast(string $type): bool
    {
        return $this->default->needsCast($type);
    }

    /**
     * {@inheritdoc}
     */
    public function cast(string $type): ?string
    {
        return $this->default->cast($type);
    }
}
