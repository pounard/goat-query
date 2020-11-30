<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Driver\Configuration;
use Goat\Driver\Platform\Platform;
use Goat\Driver\Query\FormattedQuery;
use Goat\Query\QueryError;
use Goat\Runner\AbstractResultIterator;

abstract class AbstractPDORunner extends AbstractRunner
{
    private \PDO $connection;
    /** @var string[] */
    private array $prepared = [];

    /**
     * Default constructor
     */
    public function __construct(Platform $platform, Configuration $configuration, \PDO $connection)
    {
        parent::__construct($platform, $configuration);

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
     * Convert exception.
     */
    abstract protected function convertPdoError(\PDOException $e): \Throwable;

    /**
     * {@inheritdoc}
     */
    protected function doExecute(string $sql, array $args, array $options): AbstractResultIterator
    {
        try {
            $statement = $this->connection->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($args);

            return new PDOResultIterator($statement);

        } catch (\PDOException $e) {
            throw $this->convertPdoError($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doPerform(string $sql, array $args, array $options): int
    {
        try {
            $statement = $this->connection->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $statement->execute($args);

            return $statement->rowCount();

        } catch (\PDOException $e) {
            throw $this->convertPdoError($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doPrepareQuery(string $identifier, FormattedQuery $prepared, array $options): void
    {
        // @merge argument types from query
        $this->prepared[$identifier] = [
            $this->connection->prepare(
                $prepared->toString(),
                [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
            ),
            $prepared,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecutePreparedQuery(string $identifier, array $args, array $options): AbstractResultIterator
    {
        if (!isset($this->prepared[$identifier])) {
            throw new QueryError(\sprintf("'%s': query was not prepared", $identifier));
        }

        list($statement, $prepared) = $this->prepared[$identifier];
        \assert($prepared instanceof FormattedQuery);

        $args = $prepared->prepareArgumentsWith($this->converter, $args);

        try {
            $statement->execute($args);

            return new PDOResultIterator($statement);

        } catch (\PDOException $e) {
            throw $this->convertPdoError($e);
        }
    }
}
