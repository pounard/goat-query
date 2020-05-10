<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

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

abstract class AbstractPDORunner extends AbstractRunner
{
    /** @var \PDO */
    private $connection;

    /** @var string[] */
    private $prepared = [];

    /**
     * Default constructor
     */
    public function __construct(Platform $platform, \PDO $connection)
    {
        parent::__construct($platform);

        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function isResultMetadataSlow(): bool
    {
        return true;
    }

    /**
     * Get PDO instance, connect if not connected
     */
    final protected function getPdo(): \PDO
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    protected function doCreateResultIterator(...$constructorArgs): AbstractResultIterator
    {
        return new PDOResultIterator(...$constructorArgs);
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

        $args = [];
        $rawSQL = '';

        try {
            $profiler = QueryProfiler::start();

            $profiler->start('prepare');
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();
            $args = $prepared->prepareArgumentsWith($this->converter, $query, $arguments);
            $profiler->end('prepare');

            $profiler->start('execute');
            $statement = $this->connection->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($args);
            $profiler->end('execute');

            return $this->createResultIterator($prepared->getIdentifier(), $profiler, $options, $statement);

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\PDOException $e) {
            throw new ServerError($rawSQL, $arguments, $e);
        } catch (\Exception $e) {
            throw new ServerError($rawSQL, $arguments, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function perform($query, $arguments = null, $options = null) : int
    {
        $args = [];
        $rawSQL = '';

        try {
            $profiler = QueryProfiler::start();

            $profiler->start('prepare');
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();
            $args = $prepared->prepareArgumentsWith($this->converter, $query, $arguments);
            $profiler->end('prepare');

            $profiler->start('execute');
            $statement = $this->connection->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($args);
            $profiler->end('execute');

            $profiler->stop();

            // @todo How to fetch result profile?
            return $statement->rowCount();

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\PDOException $e) {
            throw new ServerError($rawSQL, $arguments, $e);
        } catch (\Exception $e) {
            throw new ServerError($rawSQL, $arguments, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, string $identifier = null): string
    {
        $rawSQL = '';

        try {
            $profiler = QueryProfiler::start();

            $profiler->start('prepare');
            $prepared = $this->formatter->prepare($query);
            $rawSQL = $prepared->getRawSQL();
            $profiler->stop('prepare');

            if (null === $identifier) {
                $identifier = \md5($rawSQL);
            }
            // @merge argument types from query

            $profiler->start('execute');
            $this->prepared[$identifier] = [
                $this->connection->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]),
                $prepared,
            ];
            $profiler->end('execute');

            $profiler->stop();

            return $identifier;

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\PDOException $e) {
            throw new ServerError($rawSQL, [], $e);
        } catch (\Exception $e) {
            throw new ServerError($rawSQL, [], $e);
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

        list($statement, $prepared) = $this->prepared[$identifier];
        \assert($prepared instanceof FormattedQuery);

        try {
            $profiler = QueryProfiler::start();

            $profiler->begin('prepare');
            $args = $prepared->prepareArgumentsWith($this->converter, '', $arguments);
            $profiler->end('prepare');

            $profiler->begin('execute');
            $statement->execute($args);
            $profiler->end('execute');

            return $this->createResultIterator($identifier, $profiler, $options, $statement);

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ServerError($identifier, [], $e);
        }
    }
}
