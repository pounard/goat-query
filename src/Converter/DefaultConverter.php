<?php

declare(strict_types=1);

namespace Goat\Converter;

use Goat\Converter\Impl\BinaryValueConverter;
use Goat\Converter\Impl\BooleanValueConverter;
use Goat\Converter\Impl\DateValueConverter;
use Goat\Converter\Impl\IntervalValueConverter;
use Goat\Converter\Impl\JsonValueConverter;
use Goat\Converter\Impl\NumberValueConverter;
use Goat\Converter\Impl\RamseyUuidValueConverter;
use Goat\Converter\Impl\TextValueConverter;
use Goat\Runner\SessionConfiguration;
use Ramsey\Uuid\UuidInterface;

/**
 * Support PHP to SQL and SQL to PHP value conversion registry.
 *
 * You may register any number of instances, each input or output value
 * converter declares the types it supports. SQL types can be aliased
 * to any number of aliases, allowing the user to give its own type aliases
 * and support variations between RDBMS servers own dialects.
 *
 * Internally, it keeps a both flat maps of PHP to SQL and SQL to PHP
 * supported type convertions.
 *
 * If the user asks for a non-possible convertion, three different behaviours
 * are possible, and determined at call-site:
 *
 *  - Let the SQL string pass instead of the expected PHP typed value.
 *  - Return null if value is null.
 *  - Raise a TypeConversionError in case no converter supports the conversion.
 *
 * @todo Add support for user-given or driver-given SQL type aliases.
 * @todo Add class hierarchy and implementation lookup.
 */
final class DefaultConverter implements ConfigurableConverter
{
    /** @var array<string,string> */
    private array $aliasMap = [];
    /** @var array<string,array<string,StaticInputValueConverter>> */
    private array $inputTypeMap = [];
    /** @var array<string,array<string,StaticOutputValueConverter>> */
    private array $outputTypeMap = [];
    /** @var array<string,array<string,DyncamicInputValueConverter>> */
    private array $dynamicInputList = [];
    /** @var array<string,array<string,DynamicOutputValueConverter>> */
    private array $dynamicOutputList = [];

    public function __construct(bool $setupDateSupport = true)
    {
        // Register default necessary converters.
        $this->register(new BinaryValueConverter());
        $this->register(new BooleanValueConverter());
        $this->register(new JsonValueConverter());
        $this->register(new NumberValueConverter());
        $this->register(new TextValueConverter());

        // Add some custom aliases to remain backward compatible.
        $this->aliasMap = [
            'blob' => 'bytea',
            'bool' => 'boolean',
            'character varying' => 'varchar',
            'character' => 'char',
            'datetime' => 'timestamp',
            'double' => 'double precision',
            'float4' => 'real',
            'float8' => 'double precision',
            'int' => 'integer',
            'int2' => 'smallint',
            'int4' => 'integer',
            'int8' => 'bigint',
            'serial2' => 'smallserial',
            'serial4' => 'serial',
            'serial8' => 'bigserial',
            'string' => 'varchar',
            'timestamp without time zone' => 'timestamp',
            'timestampz' => 'timestamp with time zone',
            'timez' => 'time with time zone',
        ];

        if ($setupDateSupport) {
            $this->register(new DateValueConverter());
            $this->register(new IntervalValueConverter());
        }
        if (\interface_exists(UuidInterface::class)) {
            $this->register(new RamseyUuidValueConverter());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register(/* InputValueConverter|OutputValueConverter */ $instance): void
    {
        $ok = false;
        if ($instance instanceof StaticInputValueConverter) {
            $ok = true;
            foreach ($instance->supportedInputTypes() as $phpType => $sqlTypeList) {
                foreach ($sqlTypeList as $sqlType) {
                    // @todo warn if one instance shadows another
                    $this->inputTypeMap[$phpType][$sqlType] = $instance;
                }
            }
        }
        if ($instance instanceof StaticOutputValueConverter) {
            $ok = true;
            foreach ($instance->supportedOutputTypes() as $sqlType => $phpTypeList) {
                foreach ($phpTypeList as $phpType) {
                    // @todo warn if one instance shadows another
                    $this->outputTypeMap[$sqlType][$phpType] = $instance;
                }
            }
        }
        if ($instance instanceof DynamicInputValueConverter) {
            $ok = true;
            $this->dynamicInputList[] = $instance;
        }
        if ($instance instanceof DynamicOutputValueConverter) {
            $ok = true;
            $this->dynamicOutputList[] = $instance;
        }
        if (!$ok) {
            throw new \InvalidArgumentException(\sprintf("\$instance must implement %s or %s", InputValueConverter::class, OutputValueConverter::class));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL($value, ?string $sqlType, ?string $phpType, ?ConverterContext $context = null) /* : mixed */
    {
        if (null === $value || 'null' === $sqlType) {
            return null;
        }

        // Some drivers will convert automatically int and float values,
        // if this happens and the user didn't ask for a PHP type, then
        // return the value as-is. This also will boost performances in
        // use cases where you have lots of integer values.
        if (\is_int($value) || \is_float($value)) {
            if (!$phpType) {
                return $value;
            } else if ('int' === $phpType) {
                return (int) $value;
            } else if ('float' === $phpType) {
                return (float) $value;
            } else {
                $value = (string) $value;
            }
        } else if (!\is_string($value)) {
            throw new TypeConversionError("SQL raw value can only be int, float or string");
        }

        // Ideally this should never happen, but life is life.
        if ($sqlType) {
            $realType = $this->aliasMap[$sqlType] ?? $sqlType;
        } else {
            $realType = 'varchar';
        }

        $context = $context ?? new ConverterContext($this, SessionConfiguration::empty());

        if (\array_key_exists($realType, $this->outputTypeMap)) {
            if ($phpType) {
                $converter = $this->outputTypeMap[$realType][$phpType] ?? null;
                if ($converter) {
                    \assert($converter instanceof OutputValueConverter);
                    try {
                        return $converter->fromSQL($phpType, $realType, $value, $context);
                    } catch (TypeConversionError $e) {
                        // @todo log errors?
                    }
                }
            } else {
                foreach ($this->outputTypeMap[$realType] as $phpType => $converter) {
                    \assert($converter instanceof OutputValueConverter);
                    try {
                        return $converter->fromSQL($phpType, $realType, $value, $context);
                    } catch (TypeConversionError $e) {
                        // @todo log errors?
                    }
                }
            }
        }

        foreach ($this->dynamicOutputList as $converter) {
            \assert($converter instanceof DynamicOutputValueConverter);
            if ($converter->supportsOutput($phpType, $sqlType, $value)) {
                try {
                    return $converter->fromSQL($phpType ?? 'null', $sqlType, $value, $context);
                } catch (TypeConversionError $e) {
                    // @todo log errors?
                }
            }
        }

        throw new TypeConversionError();
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(/* mixed */ $value, ?string $sqlType, ?ConverterContext $context = null): ?string
    {
        if (null === $value || 'null' === $sqlType) {
            return null;
        }
        if (\is_resource($value)) {
            throw new TypeConversionError("Resources types are not supported yet.");
        }

        $context = $context ?? new ConverterContext($this, SessionConfiguration::empty());

        $expandedPhpType = $this->expandPhpTypeOf($value);
        $realType = $sqlType ? ($this->aliasMap[$sqlType] ?? $sqlType) : null;

        foreach ($expandedPhpType as $phpType) {
            if (\array_key_exists($phpType, $this->inputTypeMap)) {
                if ($sqlType) {
                    $converter = $this->inputTypeMap[$phpType][$realType] ?? null;
                    if ($converter) {
                        \assert($converter instanceof InputValueConverter);
                        try {
                            return $converter->toSQL($realType, $value, $context);
                        } catch (TypeConversionError $e) {
                            // @todo log errors?
                        }
                    }
                } else {
                    foreach ($this->inputTypeMap[$phpType] as $sqlType => $converter) {
                        \assert($converter instanceof InputValueConverter);
                        try {
                            return $converter->toSQL($sqlType, $value, $context);
                        } catch (TypeConversionError $e) {
                            // @todo log errors?
                        }
                    }
                }
            }
        }

        foreach ($this->dynamicInputList as $converter) {
            \assert($converter instanceof DynamicInputValueConverter);
            if ($converter->supportsInput($sqlType, $value)) {
                try {
                    return $converter->toSQL($sqlType, $value, $context);
                } catch (TypeConversionError $e) {
                    // @todo log errors?
                }
            }
        }

        throw new TypeConversionError();
    }

    /**
     * Find all applicable PHP types for the given value.
     */
    private function expandPhpTypeOf($value): array
    {
        if (\is_object($value)) {
            return \array_merge([\get_class($value)], \class_implements($value));
        }
        // @todo Handle interfaces and inheritance (write a cache incrementally).
        // @todo pph8 polyfill required here.
        return [\get_debug_type($value)];
    }
}
