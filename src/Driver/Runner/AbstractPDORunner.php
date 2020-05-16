<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Driver\Platform\Platform;
use Goat\Driver\Query\FormattedQuery;
use Goat\Query\QueryError;
use Goat\Runner\DatabaseError;
use Goat\Runner\ResultIterator;
use Goat\Runner\ServerError;

abstract class AbstractPDORunner extends AbstractRunner
{
    private \PDO $connection;
    /** @var string[] */
    private array $prepared = [];

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
    protected function doExecute(string $sql, array $args, array $options): ResultIterator
    {
        $statement = $this->connection->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $statement->execute($args);

        return new PDOResultIterator($statement);
    }

    /**
     * {@inheritdoc}
     */
    protected function doPerform(string $sql, array $args, array $options): int
    {
        $statement = $this->connection->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $statement->execute($args);

        return $statement->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function prepareQuery($query, string $identifier = null): string
    {
        $rawSQL = '';
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

            $profiler->begin('execute');
            $this->prepared[$identifier] = [
                $this->connection->prepare($rawSQL, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]),
                $prepared,
            ];
            $profiler->end('execute');

            return $identifier;

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\PDOException $e) {
            throw new ServerError($rawSQL, [], $e);
        } catch (\Exception $e) {
            throw new ServerError($rawSQL, [], $e);
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

        list($statement, $prepared) = $this->prepared[$identifier];
        \assert($prepared instanceof FormattedQuery);

        $args = null;
        $profiler = $this->startProfilerQuery();

        try {
            $profiler->begin('prepare');
            $args = $prepared->prepareArgumentsWith($this->converter, '', $arguments);
            $profiler->end('prepare');

            $profiler->begin('execute');
            $statement->execute($args);
            $profiler->end('execute');

            return $this->configureResultIterator($identifier, $profiler, new PDOResultIterator($statement), $options);

        } catch (DatabaseError $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ServerError($identifier, [], $e);
        } finally {
            $profiler->stop();
            if ($this->isDebugEnabled()) {
                $profiler->setRawSql("<execute prepared statement> " . $identifier, $args);
            }
        }
    }
}
