<?php

declare(strict_types=1);

namespace Goat\Runner\Driver;

use Goat\Runner\AbstractResultIterator;

class PDOResultIterator extends AbstractResultIterator
{
    protected $statement;
    protected $columnCount = 0;

    /**
     * Default constructor
     *
     * @param \PDOStatement $statement
     */
    public function __construct(\PDOStatement $statement)
    {
        $this->statement = $statement;
        $this->statement->setFetchMode(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnInfoFromDriver(int $index): array
    {
        $meta = $this->statement->getColumnMeta($index);

        return [$meta['name'], $this->parseType($meta['native_type'] ?? 'string')];
    }

    /**
     * {@inheritdoc}
     */
    protected function countColumnsFromDriver(): int
    {
        return $this->statement->columnCount();
    }

    /**
     * From metadata-given type, get a valid type name
     *
     * @param string $nativeType
     *
     * @return string
     */
    protected function parseType($nativeType): string
    {
        $nativeType = \strtolower($nativeType);

        switch (\strtolower($nativeType)) {

            case 'string':
            case 'var_string':
            case 'varchar':
            case 'char':
            case 'character':
                return 'varchar';

            case 'blob':
            case 'bytea':
                return 'bytea';

            case 'int8':
            case 'longlong':
                return 'int8';

            case 'int4':
            case 'long':
                return 'int4';

            case 'short':
                return 'int4';

            case 'bool':
                return 'bool';

            case 'json':
            case 'jsonb':
                return 'json';

            case 'uuid':
                return 'uuid';

            case 'datetime':
            case 'timestamp':
                return 'timestamp';

            case 'time':
                return 'time';

            case 'date':
                return 'date';

            case 'float4':
                return 'float4';

            case 'double':
            case 'float8':
                return 'float8';

            default:
                return $nativeType;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->statement as $row) {
            if ($this->columnKey) {
                yield $row[$this->columnKey] => $this->hydrate($row);
            } else {
                yield $this->hydrate($row);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countRows (): int
    {
        return $this->statement->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($name = 0)
    {
        if (\is_int($name)) {
            $name = $this->getColumnName($name);
        }

        $ret = [];

        foreach ($this as $row) {
            if ($this->columnKey) {
                $ret[$row[$this->columnKey]] = $row[$name];
            } else {
                $ret[] = $row[$name];
            }
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch()
    {
        $row = $this->statement->fetch();

        if ($row) {
            return $this->hydrate($row);
        }
    }
}
