<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Converter\Driver\PgSQLConverter;
use Goat\Driver\Platform\Platform;
use Goat\Driver\Query\FormattedQuery;
use Goat\Query\QueryError;
use Goat\Runner\AbstractResultIterator;
use Goat\Runner\DatabaseError;
use Goat\Runner\ResultIterator;
use Goat\Runner\ServerError;

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
    protected function doCreateResultIterator(...$constructorArgs) : AbstractResultIterator
    {
        return new ExtPgSQLResultIterator(...$constructorArgs);
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(string $sql, array $args, array $options): ResultIterator
    {
        $connection = $this->connection;

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
        $connection = $this->connection;

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
    public function prepareQuery($query, ?string $identifier = null): string
    {
        $rawSQL = '';
        $connection = $this->connection;
        $profiler = $this->startProfilerQuery();

        try {
            $profiler->begin('prepare');
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();
            $profiler->end('prepare');

            if (null === $identifier) {
                $identifier = \md5($rawSQL);
            }
            // @merge argument types from query

            if ($this->isDebugEnabled()) {
                $profiler->setRawSql($rawSQL, [$identifier]);
            }

            $profiler->begin('execute');
            if (false === @\pg_prepare($connection, $identifier, $rawSQL)) {
                $this->serverError($connection);
            }
            $this->prepared[$identifier] = $prepared;
            $profiler->end('prepare');

            return $identifier;

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ServerError($rawSQL, null, $e);
        } finally {
            $profiler->stop();
            if ($this->isDebugEnabled()) {
                $profiler->setRawSql($rawSQL, [$identifier]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery(string $identifier, $arguments = null, $options = null): ResultIterator
    {
        if (!isset($this->prepared[$identifier])) {
            throw new QueryError(\sprintf("'%s': query was not prepared", $identifier));
        }

        $prepared = $this->prepared[$identifier];
        \assert($prepared instanceof FormattedQuery);

        $args = null;
        $connection = $this->connection;
        $profiler = $this->startProfilerQuery();

        try {

            $profiler->begin('prepare');
            $args = $prepared->prepareArgumentsWith($this->converter, '', $arguments);
            $profiler->end('prepare');

            if ($this->isDebugEnabled()) {
                $profiler->setRawSql($identifier, $args);
            }

            $profiler->begin('execute');
            $resource = @\pg_execute($connection, $identifier, $args);
            if (false === $resource) {
                $this->serverError($connection, $identifier);
            }
            $profiler->end('execute');

            return $this->configureResultIterator($identifier, $profiler, new ExtPgSQLResultIterator($resource), $options);

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ServerError($identifier, $args, $e);
        } finally {
            $profiler->stop();
            if ($this->isDebugEnabled()) {
                $profiler->setRawSql("<execute prepared statement> " . $identifier, $args);
            }
        }
    }
}
