<?php

declare(strict_types=1);

namespace Goat\Runner\Driver;

use Goat\Converter\ConverterInterface;
use Goat\Converter\Driver\PgSQLArrayConverter;
use Goat\Converter\Driver\PgSQLConverter;
use Goat\Query\Impl\PgSQLFormatter;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\Transaction;

class PDOPgSQLRunner extends AbstractPDORunner
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return 'pgsql';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter): void
    {
        parent::setConverter(
            new PgSQLArrayConverter(
                new PgSQLConverter($converter)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createFormatter(): FormatterInterface
    {
        return new PgSQLFormatter($this->getEscaper());
    }

    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences(): array
    {
        return [
            '"',  // Identifier escape character
            '\'', // String literal escape character
            '$$', // String constant escape sequence
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string): string
    {
        return '"' . \str_replace('"', '""', $string) . '"';
    }

    /**
     * {@inheritdoc}
     */
    protected function doStartTransaction(int $isolationLevel = Transaction::REPEATABLE_READ): Transaction
    {
        return new PgSQLTransaction($this, $isolationLevel);
    }
}
