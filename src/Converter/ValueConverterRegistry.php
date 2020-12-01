<?php

declare(strict_types=1);

namespace Goat\Converter;

use Goat\Converter\Impl\DateValueConverter;
use Goat\Converter\Impl\IntervalValueConverter;

/**
 * Default converter implementation, suitable for most RDBMS.
 *
 * There are a few PostgreSQL specifics in here, but they won't hurt other
 * driver runtimes.
 *
 * @todo Should this be an interface?
 */
final class ValueConverterRegistry implements ValueConverterInterface
{
    private array $converters = [];
    /** @var array<string,ValueConverterInterface> */
    private array $convertersTypeMap = [];

    public function __construct(bool $setupDateSupport = true)
    {
        if ($setupDateSupport) {
            $this->register(new DateValueConverter());
            $this->register(new IntervalValueConverter());
        }
    }

    /**
     * Register a value converter.
     */
    public function register(ValueConverterInterface $instance): void
    {
        $this->converters[] = $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeSupported(string $type, ConverterContext $context): bool
    {
        return null !== $this->getValueConverter($type, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, /* null|int|float|string */ $value, ConverterContext $context) /* : mixed */
    {
        if ($converter = $this->getValueConverter($type, $context)) {
            return $converter->fromSQL($type, $value, $context);
        }

        throw new TypeConversionError(\sprintf("Could not find value converter for type: %s", $type));
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, /* mixed */ $value, ConverterContext $context): ?string
    {
        if ($converter = $this->getValueConverter($type, $context)) {
            return $converter->toSQL($type, $value, $context);
        }

        throw new TypeConversionError(\sprintf("Could not find value converter for type: %s", $type));
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($value, ConverterContext $context): string
    {
        foreach ($this->converters as $valueConverter) {
            \assert($valueConverter instanceof ValueConverterInterface);

            if (ConverterInterface::TYPE_UNKNOWN !== ($type = $valueConverter->guessType($value, $context))) {
                return $type;
            }
        }

        return ConverterInterface::TYPE_UNKNOWN;
    }

    private function getValueConverter(string $type, ConverterContext $context): ?ValueConverterInterface
    {
        $ret = $this->convertersTypeMap[$type] ?? $this->lookupValueConverter($type, $context);

        return false !== $ret ? $ret : null;
    }

    private function lookupValueConverter(string $type, ConverterContext $context): ?ValueConverterInterface
    {
        foreach ($this->converters as $valueConverter) {
            \assert($valueConverter instanceof ValueConverterInterface);

            if ($valueConverter->isTypeSupported($type, $context)) {
                return $valueConverter;
            }
        }

        return null;
    }
}
