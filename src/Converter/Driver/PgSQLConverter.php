<?php

namespace Goat\Converter\Driver;

use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;
use Goat\Converter\TypeConversionError;

/**
 * PostgreSQL all versions converter.
 */
class PgSQLConverter implements ConverterInterface
{
    private ConverterInterface $default;

    /**
     * Default constructor
     */
    public function __construct(ConverterInterface $default)
    {
        if (!$default instanceof DefaultConverter) {
            throw new TypeConversionError(\sprintf(
                "Converter must be an instance of '%s'",
                DefaultConverter::class
            ));
        }

        $default->register(new PgSQLArrayConverter());

        $this->default = $default;
    }

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function getClientTimeZone(): string
    {
        return $this->default->getClientTimeZone();
    }

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function setClientTimeZone(?string $clientTimeZone = null): void
    {
        $this->default->setClientTimeZone($clientTimeZone);
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpType(string $sqlType): ?string
    {
        return $this->default->getPhpType($sqlType);
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
        return $this->default->toSQL($type, $value);
    }
}
