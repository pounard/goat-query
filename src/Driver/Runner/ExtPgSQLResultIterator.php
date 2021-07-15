<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Runner\AbstractResultIterator;

class ExtPgSQLResultIterator extends AbstractResultIterator
{
    use ExtPgSQLErrorTrait;

    /** @var ?string[] */
    private $columnNameMap;

    /** @var resource */
    private $connection;

    /**
     * Default constructor
     *
     * @param resource $resource
     */
    public function __construct($resource)
    {
        $this->connection = $resource;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchColumnInfoFromDriver(int $index): array
    {
        $type = \pg_field_type($this->connection, $index);
        if (false === $type) {
            $this->resultError($this->connection);
        }

        $key = \pg_field_name($this->connection, $index);
        if (false === $key) {
            $this->resultError($this->connection);
        }

        return [$key, $type];
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchColumnsCountFromDriver(): int
    {
        $columnCount = \pg_num_fields($this->connection);
        if (false === $columnCount) {
            $this->resultError($this->connection);

            return 0;
        }

        return $columnCount;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchNextRowFromDriver(): ?array
    {
        if (null === $this->connection) {
            // Result was freed previously, which means we already completed
            // the iteration at least once.
            return null;
        }

        $row = \pg_fetch_assoc($this->connection);

        if (false === $row) {
            $this->resultError($this->connection);

            return null;
        }

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchRowCountFromDriver() : int
    {
        if (null === $this->connection) {
            // Result was freed previously, which means we already completed
            // the iteration at least once.
            return 0;
        }

        $ret = \pg_num_rows($this->connection);

        if (-1 === $ret) {
            $this->resultError($this->connection);

            return 0;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFreeResult(): void
    {
        if (null !== $this->connection) {
            \pg_free_result($this->connection);

            $this->connection = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function wasResultFreed(): bool
    {
        return null === $this->connection;
    }
}
