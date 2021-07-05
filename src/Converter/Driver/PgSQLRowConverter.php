<?php

declare(strict_types=1);

namespace Goat\Converter\Driver;

use Goat\Converter\ConverterInterface;
use Goat\Converter\ValueConverterInterface;
use Goat\Converter\ConverterContext;
use Goat\Query\QueryError;

/**
 * PostgreSQL row converter.
 */
final class PgSQLRowConverter implements ValueConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value, ConverterContext $context)
    {
        // All values will be string, and there's no way around that.
        // @todo Find a way to make this smarter.
        return PgSQLParser::parseRow($value);
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value, ConverterContext $context): ?string
    {
        if (!\is_array($value)) {
            throw new QueryError(\sprintf("ROW() converter can only convert array values."));
        }

        $converter = $context->getConverter();

        return PgSQLParser::writeRow(
            $value,
            fn ($value) => $converter->toSQL(
                $converter->guessType(
                    $value,
                    $context
                ),
                $value,
                $context
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isTypeSupported(string $type, ConverterContext $context): bool
    {
        return 'row' === $type || 'record' === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($value, ConverterContext $context): string
    {
        return ConverterInterface::TYPE_UNKNOWN;
    }
}
