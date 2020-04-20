<?php

declare(strict_types=1);

namespace Goat\Runner\Testing;

use Goat\Query\Writer\DefaultFormatter;
use Goat\Query\Writer\EscaperInterface;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\ResultIterator;
use Goat\Runner\Transaction;
use Goat\Runner\Driver\AbstractRunner;

class NullRunner extends AbstractRunner
{
    /**
     * {@inheritdoc}
     */
    protected function getEscaper(): EscaperInterface
    {
        return $this->escaper ?? ($this->escaper = new NullEscaper());
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
    protected function createFormatter(): FormatterInterface
    {
        return new DefaultFormatter(new NullEscaper());
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return 'null';
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral(string $string): string
    {
        return $this->getEscaper()->escapeLiteral($string);
    }

    /**
     * {@inheritdoc}
     */
    public function writePlaceholder(int $index): string
    {
        return $this->getEscaper()->writePlaceholder($index);
    }

    /**
     * {@inheritdoc}
     */
    public function unescapePlaceholderChar(): string
    {
        return $this->getEscaper()->unescapePlaceholderChar();
    }

    /**
     * {@inheritdoc}
     */
    public function unescapeBlob($resource): ?string
    {
        return $this->getEscaper()->unescapeBlob($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string): string
    {
        return $this->getEscaper()->escapeIdentifier($string);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLike(string $string): string
    {
        return $this->getEscaper()->escapeLike($string);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob(string $word): string
    {
        return $this->getEscaper()->escapeBlob($word);
    }

    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences(): array
    {
        return $this->getEscaper()->getEscapeSequences();
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, ?string $identifier = null): string
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }

    /**
     * {@inheritdoc}
     */
    protected function doStartTransaction(int $isolationLevel = Transaction::REPEATABLE_READ):Transaction
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery(string $identifier, $arguments = null, $options = null): ResultIterator
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, $arguments = null, $options = null): int
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }

    /**
     * {@inheritdoc}
     */
    public function execute($query, $arguments = null, $options = null): ResultIterator
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateResultIterator(...$constructorArgs) : ResultIterator
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }
}
