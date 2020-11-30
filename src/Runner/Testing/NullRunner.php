<?php

declare(strict_types=1);

namespace Goat\Runner\Testing;

use Goat\Driver\Configuration;
use Goat\Driver\Query\FormattedQuery;
use Goat\Driver\Runner\AbstractRunner;
use Goat\Runner\AbstractResultIterator;

class NullRunner extends AbstractRunner
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new NullPlatform(), new Configuration(['driver' => 'null']));
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
