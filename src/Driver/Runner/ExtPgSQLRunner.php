<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConfigurableConverter;
use Goat\Converter\Converter;
use Goat\Converter\Driver\PgSQLConverter;
use Goat\Driver\Platform\Escaper\Escaper;
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
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return 'pgsql';
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateConverter(ConfigurableConverter $decorated, Escaper $escaper): Converter
    {
        return new PgSQLConverter($decorated, $escaper);
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(string $sql, array $args, array $options): AbstractResultIterator
    {
        $connection = $this->getConnection();
        $resource = @\pg_query_params($connection, $sql, $args);

        if (!\is_resource($resource)) {
            $this->serverError($connection, $sql);
        }

        return new ExtPgSQLResultIterator($resource);
    }

    /**
     * {@inheritdoc}
     */
    protected function doPerform(string $sql, array $args, array $options): int
    {
        $connection = $this->getConnection();
        $resource = null;

        try {
            $resource = @\pg_query_params($connection, $sql, $args);
            if (!\is_resource($resource)) {
                $this->serverError($connection, $sql);
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
        $connection = $this->getConnection();

        $this->prepared[$identifier] = $prepared;

        if (false === @\pg_prepare($connection, $identifier, $prepared->toString())) {
            $this->serverError($connection);
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

        $connection = $this->getConnection();

        $prepared = $this->prepared[$identifier];
        \assert($prepared instanceof FormattedQuery);

        $args = $prepared->prepareArgumentsWith($this->createConverterContext(), $args);
        $resource = @\pg_execute($connection, $identifier, $args);
        if (false === $resource) {
            $this->serverError($connection, $identifier);
        }

        return new ExtPgSQLResultIterator($resource);
    }
}
