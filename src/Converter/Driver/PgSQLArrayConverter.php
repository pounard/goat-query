<?php

declare(strict_types=1);

namespace Goat\Converter\Driver;

use Goat\Converter\ConverterInterface;
use Goat\Converter\ValueConverterInterface;
use Goat\Converter\ConverterContext;

/**
 * PostgreSQL array converter.
 */
final class PgSQLArrayConverter implements ValueConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value, ConverterContext $context)
    {
        // First detect type, using anything we can.
        // PDO will always match _TYPE, sometime we also may have TYPE[].
        if ($subType = $this->findSubtype($type)) {
            return $this->recursiveFromSQL($subType, PgSQLParser::parseArray($value), $context);
        }

        return $context->getConverter()->fromSQL($type, $value, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value, ConverterContext $context): ?string
    {
        $converter = $context->getConverter();

        if (ConverterInterface::TYPE_UNKNOWN === $type) {
            $type = $this->guessType($value, $context);
        }

        $subType = $this->findSubtype($type);

        if (null === $value || !$subType || !\is_array($value)) {
            return $converter->toSQL($subType ?? $type, $value, $context);
        }
        if (empty($value)) {
            return '{}';
        }

        return PgSQLParser::writeArray(
            $value,
            fn ($value) => $converter->toSQL($subType, $value, $context)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeSupported(string $type, ConverterContext $context): bool
    {
        return '_' === $type[0] || '[]' === \substr($type, -2);
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($value, ConverterContext $context): string
    {
        if (\is_array($value)) {
            if (empty($value)) {
                return ConverterInterface::TYPE_NULL;
            }

            return $context->getConverter()->guessType(\reset($value), $context).'[]';
        }

        return ConverterInterface::TYPE_UNKNOWN;
    }

    private function recursiveFromSQL(string $type, array $values, ConverterContext $context): array
    {
        $converter = $context->getConverter();

        return \array_map(
            fn ($value) => (\is_array($value) ?
                $this->recursiveFromSQL($type, $value, $context) :
                $converter->fromSQL($type, $value, $context)
            ),
            $values
        );
    }

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
}
