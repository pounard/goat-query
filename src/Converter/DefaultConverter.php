<?php

declare(strict_types=1);

namespace Goat\Converter;

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
    const TIMESTAMP_FORMAT = 'Y-m-d H:i:s';
    const TIMESTAMP_FORMAT_DATE = 'Y-m-d';
    const TIMESTAMP_FORMAT_TIME = 'H:i:s';
    const TIMESTAMP_FORMAT_TIME_INT = 'H:I:S';

    /**
     * Get default converter map
     *
     * Please note that definition order is significant, some converters
     * canProcess() method may short-circuit some others, the current
     * definition order is kept during converters registration.
     *
     * @return array
     *   Keys are type identifiers, values are arrays containing:
     *     - first value is the converter class name
     *     - second value is a type aliases array
     *
     * @codeCoverageIgnore
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

    private $aliasMap = [];
    private $converters = [];
    private $debug = false;
    private $uuidSupport;

    /**
     * Toggle debug mode
     *
     * @param bool $debug
     */
    public function setDebug(bool $debug = true)
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
    public function register(ValueConverterInterface $instance, array $aliases = [], $allowOverride = false)
    {
        $types = $instance->getHandledTypes();

        foreach ($types as $type) {
            if (!$allowOverride && isset($this->converters[$type])) {
                $message = \sprintf("type '%s' is already defined, using '%s' converter class", $type, \get_class($this->converters[$type]));
                if ($this->debug) {
                    throw new \InvalidArgumentException($message);
                } else {
                    \trigger_error($message, E_USER_WARNING);
                }
            }

            $this->converters[$type] = $instance;
        }

        if ($aliases) {
            foreach ($aliases as $alias) {

                $message = null;
                if (isset($this->converters[$alias])) {
                    $message = \sprintf("alias '%s' for type '%s' is already defined as a type, using '%s' converter class", $alias, $type, \get_class($this->converters[$type]));
                } else if (!$allowOverride && isset($this->aliasMap[$alias])) {
                    $message = \sprintf("alias '%s' for type '%s' is already defined, for type '%s'", $alias, $type, \get_class($this->aliasMap[$type]));
                }
                if ($message) {
                    if ($this->debug) {
                        throw new \InvalidArgumentException($message);
                    } else {
                        \trigger_error($message, E_USER_WARNING);
                    }
                }

                $this->aliasMap[$alias] = $type;
            }
        }

        return $this;
    }

    /**
     * Does it supports UUID
     */
    private function supportsUuid(): bool
    {
        return $this->uuidSupport ?? ($this->uuidSupport = \class_exists(Uuid::class));
    }

    /**
     * Get converter for type
     */
    private function get(string $type) : ?ValueConverterInterface
    {
        if (isset($this->aliasMap[$type])) {
            $type = $this->aliasMap[$type];
        }

        if (isset($this->converters[$type])) {
            return $this->converters[$type];
        }

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

            // Timestamp, Date without time and time without date
            case 'datetime':
            case 'timestamp':
            case 'timestampz':
            case 'date':
            case 'time':
            case 'timez':
                return '\\DateTimeImmutable';

            // Binary objects
            // @todo handle object stream
            case 'blob':
            case 'bytea':
                return 'string';
        }

        if ($converter = $this->get($type)) {
            return $converter->getPhpType($type);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value)
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
                if (!$value || 'f' === $value || 'F' === $value || 'FALSE' === \strtolower($value)) {
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

            // Timestamp, Date without time and time without date
            case 'datetime':
            case 'timestamp':
            case 'timestampz':
            case 'date':
            case 'time':
            case 'timez':
                // @todo This needs a serious rewrite...
                if (!$data = \trim($value)) {
                    return null;
                }
                // Time is supposed to be standard: just attempt to find if there
                // is a timezone there, if not provide the PHP current one in the
                // \DateTime object.
                if (false !== \strpos($value, '.')) {
                    return new \DateTimeImmutable($data);
                }
                $tzId = @\date_default_timezone_get() ?? "UTC";
                return new \DateTimeImmutable($data, new \DateTimeZone($tzId));

            // Binary objects
            // @todo handle object stream
            case 'blob':
            case 'bytea':
                return $value;
        }

        if ($converter = $this->get($type)) {
            return $converter->fromSQL($type, $value);
        }

        return (string)$value;
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

        foreach ($this->converters as $type => $converter) {
            if ($converter->canProcess($value)) {
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
                return \json_encode($value, true);

            // UUID
            case 'uuid':
                return (string)$value;

            // Timestamp
            //
            // Default implementation don't care about timezone, most RDBMS
            // won't store timezones for you, or proceed to automatic convertions
            // depending on the server locale/timezone (for example MySQL) more
            // specific timezeone handling will be implemened within each driver
            // that supports it.
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

            // Binary objects
            // @todo handle object stream
            case 'blob':
            case 'bytea':
                return (string)$value;
        }

        if ($converter = $this->get($type)) {
            return $converter->toSQL($type, $value);
        }

        return (string)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function cast(string $type) : ?string
    {
        switch ($type) {
            // Timestamp
            case 'datetime':
            case 'timestamp':
            case 'timestampz':
                return 'timestamp';

            // Date without time
            case 'date':
                return 'date';

            // Time without date
            case 'time':
            case 'timez':
                return 'time';
        }

        if ($converter = $this->get($type)) {
            return $converter->cast($type);
        }

        return null;
    }
}
