<?php

declare(strict_types=1);

namespace Goat\Driver\Runner;

use Goat\Runner\AbstractResultIterator;

class PDOResultIterator extends AbstractResultIterator
{
    /** @var \PDOStatement */
    private $statement;

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
    protected function doFetchColumnInfoFromDriver(int $index): array
    {
        $meta = $this->statement->getColumnMeta($index);

        return [$meta['name'], $this->parseType($meta['native_type'] ?? 'string')];
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchColumnsCountFromDriver(): int
    {
        return $this->statement->columnCount();
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchNextRowFromDriver(): ?array
    {
        $row = $this->statement->fetch();

        if (false === $row) {
            return null;
        }

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchRowCountFromDriver(): int
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

        foreach ($this->statement as $row) {
            $value = $row[$name];

            if ($this->columnKey) {
                $ret[$row[$this->columnKey]] = $this->convertValue($name, $value);
            } else {
                $ret[] = $this->convertValue($name, $value);
            }
        }

        return $ret;
    }

    /**
     * PDO metadata is rather inconsistent with types.
     */
    private function parseType(string $nativeType): string
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
}
