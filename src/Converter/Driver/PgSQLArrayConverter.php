<?php

declare(strict_types=1);

namespace Goat\Converter\Driver;

use Goat\Converter\ConverterInterface;

/**
 * In PgSQL, array are types, you can't have an array with different types within.
 *
 * For now, there are important considerations you should be aware of:
 *   - it won't convert bothways, only from SQL to PHP.
 */
final class PgSQLArrayConverter implements ConverterInterface
{
    private $converter;

    /**
     * We are going to work recursively, we need a converter here
     */
    public function __construct(ConverterInterface $converter)
    {
        $this->converter = $converter;
    }

    /**
     * Find subtype
     */
    private function findSubtype(string $type): ?string
    {
        if ('_' === $type[0]) {
            return \mb_substr($type, 1);
        }
        if ('[]' === \mb_substr($type, -2)) {
            return \mb_substr($type, 0, -2);
        }
        return null;
    }

    /**
     * Recursive convertion
     */
    private function recursiveFromSQL(string $type, array $values): array
    {
        return \array_map(
            function ($value) use ($type) {
                if (\is_array($value)) {
                    return $this->recursiveFromSQL($type, $value);
                }
                return $this->converter->fromSQL($type, $value);
            },
            $values
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpType(string $sqlType): ?string
    {
        if ($this->findSubtype($sqlType)) {
            return 'array';
        }

        return $this->converter->getPhpType($sqlType);
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value)
    {
        // First detect type, using anything we can.
        // PDO will always match _TYPE, sometime we also may have TYPE[].
        if ($subType = $this->findSubtype($type)) {
            return $this->recursiveFromSQL($subType, PgSQLParser::parseArray($value));
        }

        return $this->converter->fromSQL($type, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value): ?string
    {
        // @todo We do not handle this way conversion for now
        return $this->converter->toSQL($type, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function cast(string $type): ?string
    {
        if ($subType = $this->findSubtype($type)) {
            return \sprintf("%s[]", $subType);
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($value): string
    {
        // @todo We do not handle this way conversion for now
        return $this->converter->guessType($value);
    }
}