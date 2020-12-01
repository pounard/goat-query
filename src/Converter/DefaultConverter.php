<?php

declare(strict_types=1);

namespace Goat\Converter;

use Ramsey\Uuid\Uuid;

/**
 * Default converter implementation, suitable for most drivers.
 *
 * Extend this class if your driver need to override or add specific conversion
 * procedures.
 *
 * Please note there are a few PostgreSQL specifics in here, but they will not
 * hurt other driver runtimes.
 */
class DefaultConverter implements ConverterInterface
{
    private ValueConverterRegistry $valueConverterRegistry;
    private ?bool $uuidSupport = null;

    public function __construct()
    {
        $this->setValueConverterRegistry(new ValueConverterRegistry());
    }

    /**
     * {@inheritdoc}
     */
    public function setValueConverterRegistry(ValueConverterRegistry $valueConverterRegistry): void
    {
        $this->valueConverterRegistry = $valueConverterRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeSupported(string $type, ConverterContext $context): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value, ConverterContext $context)
    {
        // Null values are null.
        if (null === $value) {
            return null;
        }

        switch ($type) {
            case ConverterInterface::TYPE_NULL:
                return null;

            // Serial (integers)
            case 'bigserial':
            case 'serial':
            case 'serial2':
            case 'serial4':
            case 'serial8':
            case 'smallserial':
            // Integers
            case 'bigint':
            case 'int':
            case 'int2':
            case 'int4':
            case 'int8':
            case 'integer':
            case 'smallint':
                return (int) $value;

            // Strings
            case 'char':
            case 'character':
            case 'text':
            case 'varchar':
                return $value;

            // Flaoting point numbers and decimals
            case 'decimal':
            case 'double':
            case 'float4':
            case 'float8':
            case 'numeric':
            case 'real':
                return (float) $value;

            // Booleans
            case 'bool':
            case 'boolean':
                // When used with Doctrine, some types are already converted
                if (\is_bool($value)) {
                    return $value;
                }
                if (!$value || 'f' === $value || 'F' === $value || 'false' === \strtolower($value)) {
                    return false;
                }
                return (bool) $value;

            // JSON
            case 'json':
            case 'jsonb':
                return \json_decode($value, true);

            // UUID
            case 'uuid':
                if ($this->supportsUuid()) {
                    return Uuid::fromString($value);
                }
                return (string) $value;

            // Binary objects.
            // Some low level drivers may give back streams or escaped strings
            // when requesting blob/bytea values. Blob as stream is handled in
            // generic \Goat\Driver\Runner\RunnerConverter implementation.
            // (un)escaping is done by Escaper instance that the platform gave
            // to the runner.
            case 'blob':
            case 'bytea':
                return $value;

            default:
                try {
                    return $this->valueConverterRegistry->fromSQL($type, $value, $context);
                } catch (TypeConversionError $e) {
                    return (string) $value;
                }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value, ConverterContext $context) : ?string
    {
        // Null values are null.
        if (null === $value) {
            return null;
        }

        if (ConverterInterface::TYPE_UNKNOWN === $type) {
            $type = $this->guessType($value, $context);
        }

        switch ($type) {
            case ConverterInterface::TYPE_NULL:
                return null;

            // Serial (integers)
            case 'bigserial':
            case 'serial':
            case 'serial2':
            case 'serial4':
            case 'serial8':
            case 'smallserial':
            // Integers
            case 'bigint':
            case 'int':
            case 'int2':
            case 'int4':
            case 'int8':
            case 'integer':
            case 'smallint':
                return (string)(int) $value;

            // Strings
            case 'char':
            case 'character':
            case 'clog':
            case 'text':
            case 'varchar':
                return (string) $value;

            // Flaoting point numbers and decimals
            case 'decimal':
            case 'double':
            case 'float4':
            case 'float8':
            case 'numeric':
            case 'real':
                return (string)(float) $value;

            // Booleans
            case 'bool':
            case 'boolean':
                return $value ? 't' : 'f';

            // JSON
            case 'json':
            case 'jsonb':
                return \json_encode($value);

            // UUID
            case 'uuid':
                return (string) $value;

            // Binary objects.
            // Some low level drivers may give back streams or escaped strings
            // when requesting blob/bytea values. Blob as stream is handled in
            // generic \Goat\Driver\Runner\RunnerConverter implementation.
            // (un)escaping is done by Escaper instance that the platform gave
            // to the runner.
            case 'blob':
            case 'bytea':
                return (string) $value;

            default:
                try {
                    return $this->valueConverterRegistry->toSQL($type, $value, $context);
                } catch (TypeConversionError $e) {
                    return (string) $value;
                }
        }
    }


    /**
     * {@inheritdoc}
     */
    public function guessType($value, ConverterContext $context) : string
    {
        if (null === $value) {
            return ConverterInterface::TYPE_NULL;
        }
        if (\is_int($value) || \is_string($value)) {
            return 'varchar';
        }
        if (\is_bool($value)) {
            return 'bool';
        }
        if (\is_float($value) || \is_numeric($value)) {
            return 'numeric';
        }
        if ($value instanceof \DateTimeInterface) {
            return 'timestamptz';
        }

        $type = $this->valueConverterRegistry->guessType($value, $context);

        return ConverterInterface::TYPE_UNKNOWN === $type ? 'varchar' : $type;
    }

    /**
     * Does it supports UUID
     */
    private function supportsUuid(): bool
    {
        return $this->uuidSupport ?? ($this->uuidSupport = \class_exists(Uuid::class));
    }
}
