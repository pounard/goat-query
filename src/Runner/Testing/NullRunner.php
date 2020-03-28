<?php

declare(strict_types=1);

namespace Goat\Runner\Testing;

use Goat\Driver\Runner\AbstractRunner;
use Goat\Runner\AbstractResultIterator;
use Goat\Runner\ResultIterator;

class NullRunner extends AbstractRunner
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new NullPlatform());
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
    public function prepareQuery($query, ?string $identifier = null): string
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
    protected function doCreateResultIterator(...$constructorArgs) : AbstractResultIterator
    {
        throw new \Exception("Null runner cannot actually run queries.");
    }
}
