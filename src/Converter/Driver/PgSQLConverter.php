<?php

namespace Goat\Converter\Driver;

use Goat\Converter\ConverterInterface;
use Goat\Converter\TypeConversionError;

/**
 * PostgreSQL all versions converter.
 */
class PgSQLConverter implements ConverterInterface
{
    const TIMESTAMP_FORMAT = 'Y-m-d H:i:s.uP';
    const TIMESTAMP_FORMAT_DATE = 'Y-m-d';
    const TIMESTAMP_FORMAT_TIME = 'H:i:s.uP';
    const TIMESTAMP_FORMAT_TIME_INT = 'H:I:S';

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

            // Timestamp - Implement timezoning
            case 'datetime':
            case 'timestamp':
            case 'timestampz':
                if (!$value instanceof \DateTimeInterface) {
                    throw new TypeConversionError(\sprintf("given value '%s' is not instanceof \DateTimeInterface", $value));
                }
                return $value->format(self::TIMESTAMP_FORMAT);

            // Date without time
            case 'date':
                if (!$value instanceof \DateTimeInterface) {
                    throw new TypeConversionError(\sprintf("given value '%s' is not instanceof \DateTimeInterface", $value));
                }
                return $value->format(self::TIMESTAMP_FORMAT_DATE);

            // Time without date
            case 'time':
            case 'timez':
                if ($value instanceof \DateTimeInterface) {
                    return $value->format(self::TIMESTAMP_FORMAT_TIME);
                }
                if ($value instanceof \DateInterval) {
                    return $value->format(self::TIMESTAMP_FORMAT_TIME_INT);
                }
                throw new TypeConversionError(\sprintf("given value '%s' is not instanceof \DateTimeInterface not \DateInterval", $value));
        }

        return $this->default->toSQL($type, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function cast(string $type): ?string
    {
        return $this->default->cast($type);
    }
}
