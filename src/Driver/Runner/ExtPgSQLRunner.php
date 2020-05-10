<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Converter\Driver\PgSQLConverter;
use Goat\Driver\Instrumentation\QueryProfiler;
use Goat\Driver\Platform\Platform;
use Goat\Driver\Query\FormattedQuery;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Runner\AbstractResultIterator;
use Goat\Runner\DatabaseError;
use Goat\Runner\EmptyResultIterator;
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
    private $prepared = [];

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
    public function execute($query, $arguments = null, $options = null): ResultIterator
    {
        if ($query instanceof Query) {
            if (!$query->willReturnRows()) {
                $affectedRowCount = $this->perform($query, $arguments, $options);

                return new EmptyResultIterator($affectedRowCount);
            }
        }

        $rawSQL = '';
        $connection = $this->connection;

        try {
            $profiler = QueryProfiler::start();

            $profiler->start('prepare');
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();
            $args = $prepared->prepareArgumentsWith($this->converter, $query, $arguments);
            $profiler->end('prepare');

            $profiler->start('execute');
            $resource = @\pg_query_params($connection, $rawSQL, $args);
            if (false === $resource) {
                $this->serverError($connection, $rawSQL);
            }
            $profiler->start('execute');

            return $this->createResultIterator($prepared->getIdentifier(), $profiler, $options, $resource);

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ServerError($rawSQL, $args, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, $arguments = null, $options = null): int
    {
        $rawSQL = '';
        $connection = $this->connection;

        try {
            $profiler = QueryProfiler::start();

            $profiler->start('prepare');
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();
            $args = $prepared->prepareArgumentsWith($this->converter, $query, $arguments);
            $profiler->end('prepare');

            $profiler->start('execute');
            $resource = @\pg_query_params($connection, $rawSQL, $args);
            if (false === $resource) {
                $this->serverError($connection, $rawSQL);
            }
            $profiler->end('execute');

            $rowCount = @\pg_affected_rows($resource);
            if (false === $rowCount) {
                $this->resultError($resource);
            }

            // No need to keep any result into memory.
            @\pg_free_result($resource);

            $profiler->stop();

            // @todo How to fetch result profile?
            return $rowCount;

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ServerError($rawSQL, $args, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, ?string $identifier = null): string
    {
        $rawSQL = '';
        $connection = $this->connection;

        try {
            $profiler = QueryProfiler::start();

            $profiler->start('prepare');
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();
            $profiler->end('prepare');

            if (null === $identifier) {
                $identifier = \md5($rawSQL);
            }
            // @merge argument types from query

            $profiler->start('execute');
            if (false === @\pg_prepare($connection, $identifier, $rawSQL)) {
                $this->serverError($connection);
            }
            $this->prepared[$identifier] = $prepared;
            $profiler->end('prepare');

            $profiler->stop();

            return $identifier;

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ServerError($rawSQL, null, $e);
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

        $connection = $this->connection;

        try {
            $profiler = QueryProfiler::start();

            $profiler->start('prepare');
            $args = $prepared->prepareArgumentsWith($this->converter, '', $arguments);
            $profiler->end('prepare');

            $profiler->start('execute');
            $resource = @\pg_execute($connection, $identifier, $args);
            if (false === $resource) {
                $this->serverError($connection, $identifier);
            }
            $profiler->end('execute');

            return $this->createResultIterator($identifier, $profiler, $options, $resource);

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ServerError($identifier, $args, $e);
        }
    }
}
