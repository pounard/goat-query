<?php

declare(strict_types=1);

namespace Goat\Converter;

use Goat\Converter\Impl\DateValueConverter;
use Goat\Converter\Impl\IntervalValueConverter;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Converter map contains references to all existing converters and is the
 * central point of all native to SQL or SQL to native type conversion.
 *
 * For speed, this implementation will proceed to primitive, SQL common and
 * a few engine specific types convertion.
 */
final class DefaultConverter implements ConverterInterface
{
    /**
     * Get default converter map
     *
     * @return array
     *   Keys are type identifiers, values are arrays containing:
     *     - first value is the converter class name
     *     - second value is a type aliases array
     *
     * @codeCoverageIgnore
     * @deprecated
     */
    public static function getDefautConverterMap() : array
    {
        /*
         * Mapping from PostgreSQL 9.2
         *
            # bigint 	int8 	signed eight-byte integer
            # bigserial 	serial8 	autoincrementing eight-byte integer
            bit [ (n) ] 	  	fixed-length bit string
            bit varying [ (n) ] 	varbit 	variable-length bit string
            # boolean 	bool 	logical Boolean (true/false)
            box 	  	rectangular box on a plane
            # bytea 	  	binary data (“byte array”)
            # character [ (n) ] 	char [ (n) ] 	fixed-length character string
            # character varying [ (n) ] 	varchar [ (n) ] 	variable-length character string
            cidr 	  	IPv4 or IPv6 network address
            circle 	  	circle on a plane
            # date 	  	calendar date (year, month, day)
            # double precision 	float8 	double precision floating-point number (8 bytes)
            inet 	  	IPv4 or IPv6 host address
            # integer 	int, int4 	signed four-byte integer
            # interval [ fields ] [ (p) ] 	  	time span
            # json 	  	textual JSON data
            # jsonb 	  	binary JSON data, decomposed
            line 	  	infinite line on a plane
            lseg 	  	line segment on a plane
            macaddr 	  	MAC (Media Access Control) address
            macaddr8 	  	MAC (Media Access Control) address (EUI-64 format)
            money 	  	currency amount
            # numeric [ (p, s) ] 	decimal [ (p, s) ] 	exact numeric of selectable precision
            path 	  	geometric path on a plane
            pg_lsn 	  	PostgreSQL Log Sequence Number
            point 	  	geometric point on a plane
            polygon 	  	closed geometric path on a plane
            # real 	float4 	single precision floating-point number (4 bytes)
            # smallint 	int2 	signed two-byte integer
            # smallserial 	serial2 	autoincrementing two-byte integer
            # serial 	serial4 	autoincrementing four-byte integer
            # text 	  	variable-length character string
            # time [ (p) ] [ without time zone ] 	  	time of day (no time zone)
            # time [ (p) ] with time zone 	timetz 	time of day, including time zone
            # timestamp [ (p) ] [ without time zone ] 	  	date and time (no time zone)
            # timestamp [ (p) ] with time zone 	timestamptz 	date and time, including time zone
            tsquery 	  	text search query
            tsvector 	  	text search document
            txid_snapshot 	  	user-level transaction ID snapshot
            # uuid 	  	universally unique identifier
            xml 	  	XML data
         */

        // Kept interval in here for the sake of example.
        // @todo move it out when there will be a proper documentation
        return [
            [IntervalValueConverter::class, []],
        ];
    }

    private string $clientTimeZone;
    /** @var ValueConverterInterface[] */
    private array $converters = [];
    /** @var array<string,ValueConverterInterface> */
    private array $convertersTypeMap = [];
    private bool $debug = false;
    private ?bool $uuidSupport = null;

    public function __construct(bool $setupDateSupport = true)
    {
        if ($setupDateSupport) {
            $this->register(new DateValueConverter());
            $this->register(new IntervalValueConverter());
        }
        $this->clientTimeZone = @\date_default_timezone_get() ?? 'UTC';
    }

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function getClientTimeZone(): string
    {
        return $this->clientTimeZone;
    }

    /**
     * @deprecated
     */
    public function setClientTimeZone(?string $clientTimeZone = null): void
    {
        $this->clientTimeZone = $clientTimeZone ?? @\date_default_timezone_get() ?? 'UTC';
    }

    /**
     * Toggle debug mode
     *
     * @param bool $debug
     */
    public function setDebug(bool $debug = true): void
    {
        $this->debug = $debug;
    }

    /**
     * Register a converter
     *
     * @param ValueConverterInterface $instance
     * @param string[] $aliases
     *
     * @return $this
     */
    public function register(ValueConverterInterface $instance): void
    {
        $this->converters[] = $instance;
    }

    /**
     * Does it supports UUID
     */
    private function supportsUuid(): bool
    {
        return $this->uuidSupport ?? ($this->uuidSupport = \class_exists(Uuid::class));
    }

    /**
     * Lookup in value converters to find one that can handle this type
     */
    private function findValueConverter(string $type): ?ValueConverterInterface
    {
        /** @var \Goat\Converter\ValueConverterInterface $valueConverter */
        foreach ($this->converters as $valueConverter) {
            if ($valueConverter->isTypeSupported($type, $this)) {
                return $valueConverter;
            }
        }
        return null;
    }

    /**
     * Get converter for type
     */
    private function getValueConverter(string $type): ?ValueConverterInterface
    {
        if (isset($this->convertersTypeMap[$type])) {
            $valueConverter = $this->convertersTypeMap[$type];

            if (false === $valueConverter) {
                return null;
            }

            return $valueConverter;
        }

        if ($valueConverter = $this->findValueConverter($type)) {
            return $this->convertersTypeMap[$type] = $valueConverter;
        }

        $this->convertersTypeMap[$type] = false;

        if ($this->debug) {
            throw new \InvalidArgumentException(\sprintf("no converter registered for type '%s'", $type));
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpType(string $type): ?string
    {
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
                return 'int';

            // Strings
            case 'char':
            case 'character':
            case 'text':
            case 'varchar':
                return 'string';

            // Flaoting point numbers and decimals
            case 'decimal':
            case 'double':
            case 'float4':
            case 'float8':
            case 'numeric':
            case 'real':
                return 'float';

            // Booleans
            case 'bool':
            case 'boolean':
                return 'bool';

            // JSON
            case 'json':
            case 'jsonb':
                return '\\stdClass';

            // UUID
            case 'uuid':
                if ($this->supportsUuid()) {
                    return UuidInterface::class;
                }
                return 'string'; // @todo third party library support ?

            // Binary objects
            // @todo handle object stream
            case 'blob':
            case 'bytea':
                return 'string';

            default:
                // @todo This can be optimized by adding an array cache.
                foreach ($this->converters as $valueConverter) {
                    if ($phpType = $valueConverter->getPhpType($type)) {
                        return $phpType;
                    }
                }
                return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value)
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
                return (int)$value;

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
                return (float)$value;

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
                return (bool)$value;

            // JSON
            case 'json':
            case 'jsonb':
                return \json_decode($value, true);

            // UUID
            case 'uuid':
                if ($this->supportsUuid()) {
                    return Uuid::fromString($value);
                }
                return (string)$value;

            // Binary objects
            // @todo handle object stream
            case 'blob':
            case 'bytea':
                return $value;

            default:
                if ($converter = $this->getValueConverter($type)) {
                    return $converter->fromSQL($type, $value, $this);
                }
                return (string)$value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($value) : string
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
            return 'timestamp';
        }

        /** @var \Goat\Converter\ValueConverterInterface $valueConverter */
        foreach ($this->converters as $valueConverter) {
            if ($type = $valueConverter->guessType($value, $this)) {
                return $type;
            }
        }

        return 'varchar';
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value) : ?string
    {
        // Null values are null.
        if (null === $value) {
            return null;
        }

        if (ConverterInterface::TYPE_UNKNOWN === $type) {
            $type = $this->guessType($value);
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
                return (string)(int)$value;

            // Strings
            case 'char':
            case 'character':
            case 'clog':
            case 'text':
            case 'varchar':
                return (string)$value;

            // Flaoting point numbers and decimals
            case 'decimal':
            case 'double':
            case 'float4':
            case 'float8':
            case 'numeric':
            case 'real':
                return (string)(float)$value;

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
                return (string)$value;

            // Binary objects
            // @todo handle object stream
            case 'blob':
            case 'bytea':
                return (string)$value;

            default:
                if ($converter = $this->getValueConverter($type)) {
                    return $converter->toSQL($type, $value, $this);
                }
                return (string)$value;
        }
    }
}
