<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Runner\AbstractResultIterator;
use Goat\Runner\InvalidDataAccessError;

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

    /**
     * fetchColumn() implementation that returns a keyed array
     */
    protected function fetchColumnWithKey(string $name, int $index, string $columnKeyName)
    {
        if (null === $this->connection) {
            throw new InvalidDataAccessError("Result was closed");
        }

        $keyIndex = $this->getColumnNumber($columnKeyName);

        // @todo this is not scalable, but fetchColumn() signature isn't as well
        //   because we shouldn't return an array, but an iterable (stream).
        $valueColumn = \pg_fetch_all_columns($this->connection, $index);
        if (false === $valueColumn) {
            // @todo use ExtPgSQLErrorTrait to provide more information
            //   and nest the SQL error as previous of this one.
            throw new InvalidDataAccessError(\sprintf("column '%d' is out of scope of the current result", $index));
        }

        $indexColumn = \pg_fetch_all_columns($this->connection, $keyIndex);
        if (false === $indexColumn) {
            // @todo use ExtPgSQLErrorTrait to provide more information
            //   and nest the SQL error as previous of this one.
            throw new InvalidDataAccessError(\sprintf("column '%d' is out of scope of the current result", $keyIndex));
        }

        $ret = [];

        foreach ($valueColumn as $index => $value) {
            $ret[$indexColumn[$index]] = $this->convertValue($name, $value);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($name = 0)
    {
        if (null === $this->connection) {
            throw new InvalidDataAccessError("Result was closed");
        }

        if (\is_string($name)) {
            $index = $this->getColumnNumber($name);
        } else {
            $index = (int)$name;
            $name = $this->getColumnName($index);
        }

        if ($this->columnKey) {
            return $this->fetchColumnWithKey($name, $index, $this->columnKey);
        }

        $ret = [];

        $columns = \pg_fetch_all_columns($this->connection, $index);
        if (false === $columns) {
            // @todo use ExtPgSQLErrorTrait to provide more information
            //   and nest the SQL error as previous of this one.
            throw new InvalidDataAccessError(\sprintf("column '%d' is out of scope of the current result", $index));
        }

        foreach ($columns as $value) {
            $ret[] = $this->convertValue($name, $value);
        }

        return $ret;
    }
}
