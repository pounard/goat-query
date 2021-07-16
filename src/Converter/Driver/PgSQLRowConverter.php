<?php

declare(strict_types=1);

namespace Goat\Converter\Driver;

use Goat\Converter\ConverterContext;
use Goat\Converter\DynamicInputValueConverter;
use Goat\Converter\DynamicOutputValueConverter;
use Goat\Query\QueryError;

/**
 * PostgreSQL (composite type, record, row) converter.
 *
 * @see https://www.postgresql.org/docs/13/rowtypes.html
 */
final class PgSQLRowConverter implements DynamicInputValueConverter, DynamicOutputValueConverter
{
    /**
     * {@inheritdoc}
     */
    public function supportsOutput(?string $phpType, ?string $sqlType, string $value): bool
    {
        return \str_starts_with($value, '(') && \str_starts_with($value, ')') && ($sqlType === 'record' || $sqlType === 'row');
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $phpType, ?string $sqlType, string $value, ConverterContext $context)
    {
        // All values will be string, and there's no way around that.
        // @todo Find a way to make this smarter.
        return PgSQLParser::parseRow($value);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsInput(string $sqlType, /* mixed */ $value): bool
    {
        return \is_array($value) && ($sqlType === 'record' || $sqlType === 'row');
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
                $value,
                $converter->guessType(
                    $value,
                    $context
                ),
                $context
            )
        );
    }
}
