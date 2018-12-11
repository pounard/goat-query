<?php

declare(strict_types=1);

namespace Goat\Runner\Driver;

use Goat\Converter\ConverterInterface;
use Goat\Converter\Driver\MySQLConverter;
use Goat\Query\Impl\MySQL5Formatter;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\Transaction;

class PDOMySQLRunner extends AbstractPDORunner
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return 'mysql';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter): void
    {
        parent::setConverter(new MySQLConverter($converter));
    }

    /**
     * {@inheritdoc}
     */
    protected function createFormatter(): FormatterInterface
    {
        return new MySQL5Formatter($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences(): array
    {
        return [
            '`',  // Identifier escape character
            '\'', // String literal escape character
            '"',  // String literal variant
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string): string
    {
        return '`' . \str_replace('`', '``', $string) . '`';
    }

    /**
     * {@inheritdoc}
     */
    protected function doStartTransaction(int $isolationLevel = Transaction::REPEATABLE_READ): Transaction
    {
        return new MySQLTransaction($this, $isolationLevel);
    }
}
