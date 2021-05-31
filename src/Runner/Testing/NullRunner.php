<?php

declare(strict_types=1);

namespace Goat\Runner\Testing;

use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;
use Goat\Driver\Query\FormattedQuery;
use Goat\Driver\Runner\AbstractRunner;
use Goat\Runner\AbstractResultIterator;
use Goat\Runner\SessionConfiguration;

class NullRunner extends AbstractRunner
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new NullDriver(), SessionConfiguration::empty());
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return 'null';
    }

    protected function doCreateConverter(): ConverterInterface
    {
        return new DefaultConverter();
    }

    /**
     * {@inheritdoc}
     */
    protected function doPrepareQuery(string $identifier, FormattedQuery $prepared, array $options): void
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecutePreparedQuery(string $identifier, array $args, array $options): AbstractResultIterator
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }

    /**
     * {@inheritdoc}
     */
    protected function doPerform(string $sql, array $args, array $options): int
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(string $sql, array $args, array $options): AbstractResultIterator
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }
}
