<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Converter\Driver\PgSQLConverter;
use Goat\Driver\Platform\Platform;
use Goat\Driver\Query\FormattedQuery;
use Goat\Query\QueryError;
use Goat\Runner\AbstractResultIterator;

/**
 * ext-pgsql connection implementation
 */
class ExtPgSQLRunner extends AbstractRunner
{
    use ExtPgSQLErrorTrait;

    /** @var resource<\pg_connect> */
    private $connection;
    /** @var string[] */
    private array $prepared = [];

    /**
     * Constructor
     *
     * @param resource $resource
     *   pgsql extension connection resource.
     */
    public function __construct(Platform $platform, $connection)
    {
        parent::__construct($platform);

        if (!\is_resource($connection)) {
            throw new QueryError(\sprintf("First parameter must be a resource, %s given", \gettype($connection)));
        }
        $this->connection = $connection;
    }

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
    public function setConverter(ConverterInterface $converter): void
    {
        parent::setConverter(new PgSQLConverter($converter));
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(string $sql, array $args, array $options): AbstractResultIterator
    {
        $resource = @\pg_query_params($this->connection, $sql, $args);

        if (!\is_resource($resource)) {
            $this->serverError($this->connection, $sql);
        }

        return new ExtPgSQLResultIterator($resource);
    }

    /**
     * {@inheritdoc}
     */
    protected function doPerform(string $sql, array $args, array $options): int
    {
        $resource = null;

        try {
            $resource = @\pg_query_params($this->connection, $sql, $args);
            if (!\is_resource($resource)) {
                $this->serverError($this->connection, $sql);
            }

            $rowCount = @\pg_affected_rows($resource);
            if (false === $rowCount) {
                $this->resultError($resource);
            }

            return $rowCount;

        } finally {
            // No need to keep any result into memory.
            if (\is_resource($resource)) {
                @\pg_free_result($resource);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doPrepareQuery(string $identifier, FormattedQuery $prepared, array $options): void
    {
        $this->prepared[$identifier] = $prepared;

        if (false === @\pg_prepare($this->connection, $identifier, $prepared->toString())) {
            $this->serverError($this->connection);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecutePreparedQuery(string $identifier, array $args, array $options): AbstractResultIterator
    {
        if (!isset($this->prepared[$identifier])) {
            throw new QueryError(\sprintf("'%s': query was not prepared", $identifier));
        }

        $prepared = $this->prepared[$identifier];
        \assert($prepared instanceof FormattedQuery);

        $args = $prepared->prepareArgumentsWith($this->converter, $args);
        $resource = @\pg_execute($this->connection, $identifier, $args);
        if (false === $resource) {
            $this->serverError($this->connection, $identifier);
        }

        return new ExtPgSQLResultIterator($resource);
    }
}
