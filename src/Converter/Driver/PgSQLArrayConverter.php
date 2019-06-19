<?php

declare(strict_types=1);

namespace Goat\Converter\Driver;

use Goat\Converter\ConverterInterface;
use Goat\Converter\ValueConverterInterface;

/**
 * PostgreSQL array converter.
 */
final class PgSQLArrayConverter implements ValueConverterInterface
{
    /**
     * Find subtype
     */
    private function findSubtype(string $type): ?string
    {
        if ('_' === $type[0]) {
            return \substr($type, 1);
        }
        if ('[]' === \substr($type, -2)) {
            return \substr($type, 0, -2);
        }
        return null;
    }

    /**
     * Recursive convertion
     */
    private function recursiveFromSQL(string $type, array $values, ConverterInterface $converter): array
    {
        return \array_map(
            function ($value) use ($type, $converter) {
                if (\is_array($value)) {
                    return $this->recursiveFromSQL($type, $value, $converter);
                }
                return $converter->fromSQL($type, $value);
            },
            $values
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpType(string $sqlType, ConverterInterface $converter): ?string
    {
        if ($this->findSubtype($sqlType)) {
            return 'array';
        }

        return $this->converter->getPhpType($sqlType);
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value, ConverterInterface $converter)
    {
        // First detect type, using anything we can.
        // PDO will always match _TYPE, sometime we also may have TYPE[].
        if ($subType = $this->findSubtype($type)) {
            return $this->recursiveFromSQL($subType, PgSQLParser::parseArray($value), $converter);
        }

        return $converter->fromSQL($type, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value, ConverterInterface $converter): ?string
    {
        if (ConverterInterface::TYPE_UNKNOWN === $type) {
            $type = $this->guessType($value, $converter);
        }

        $subType = $this->findSubtype($type);

        if (null === $value || !$subType || !\is_array($value)) {
            return $converter->toSQL($subType ?? $type, $value);
        }
        if (empty($value)) {
            return '{}';
        }

        return PgSQLParser::writeArray(
            $value,
            static function ($value) use ($subType, $converter) {
                return $converter->toSQL($subType, $value);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeSupported(string $type, ConverterInterface $converter): bool
    {
        return '_' === $type[0] || '[]' === \substr($type, -2);
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($value, ConverterInterface $converter): ?string
    {
        if (\is_array($value)) {
            if (empty($value)) {
                return ConverterInterface::TYPE_NULL;
            }
            return $converter->guessType(\reset($value)).'[]';
        }
        return $converter->guessType($value);
    }
}
