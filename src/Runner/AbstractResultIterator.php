<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Hydrator\HydratorInterface;
use Goat\Query\QueryError;

abstract class AbstractResultIterator implements ResultIterator
{
    private $columnCount;
    private $columnNameMap = [];
    private $columnTypeMap = [];
    private $everythingCollected = false;
    private $loadedColumns = [];
    private $userTypeMap = [];
    protected $columnKey;
    protected $converter;
    protected $hydrator;

    /**
     * Implementation of both getColumnType() and getColumnName().
     *
     * @param int $index
     *
     * @return string[]
     *   First value must be column name, second column type
     */
    abstract protected function getColumnInfoFromDriver(int $index): array;

    /**
     * Real implementation of getColumnName().
     */
    abstract protected function countColumnsFromDriver(): int;

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter): void
    {
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function setHydrator(HydratorInterface $hydrator): void
    {
        $this->hydrator = $hydrator;
    }

    /**
     * Convert a single value
     *
     * @param string $name
     * @param mixed $value
     *
     * @return mixed
     */
    protected function convertValue(string $name, $value)
    {
        if ($this->converter) {
            return $this->converter->fromSQL($this->getColumnType($name), $value);
        }

        \trigger_error("result iterator has no converter set", E_USER_WARNING);

        return $value;
    }

    /**
     * Convert values from SQL types to PHP native types
     *
     * @param string[] $row
     *   SQL fetched raw values are always strings
     *
     * @return mixed[]
     *   Same array, with converted values
     */
    protected function convertValues(array $row): array
    {
        if (!$this->converter) {
            \trigger_error("result iterator has no converter set", E_USER_WARNING);

            return $row;
        }

        $ret = [];

        foreach ($row as $name => $value) {
            $name = (string)$name; // Column name can be an integer (eg. SELECT 1 ...).
            if (null !== $value) {
                $ret[$name] = $this->converter->fromSQL($this->getColumnType($name), $value);
            } else {
                $ret[$name] = null;
            }
        }

        return $ret;
    }

    /**
     * Hydrate row using the iterator object hydrator
     *
     * @param mixed[] $row
     *   PHP native types converted values
     *
     * @return array|object
     *   Raw object, return depends on the hydrator
     */
    protected function hydrate(array $row)
    {
        $converted = $this->convertValues($row);

        if ($this->hydrator) {
            return $this->hydrator->createAndHydrateInstance($converted);
        }

        return $converted;
    }

    /**
     * {@inheritdoc}
     */
    public function setKeyColumn(string $name): ResultIterator
    {
        if (!$this->columnExists($name)) {
            throw new QueryError(\sprintf("column '%s' does not exist in result", $name));
        }

        $this->columnKey = $name;

        return $this;
    }

    /**
     * Collect single column information
     */
    private function collectColumnInfo(int $index)
    {
        if (!isset($this->loadedColumns[$index])) {
            list($name, $type) = $this->getColumnInfoFromDriver($index);
            $this->loadedColumns[$index] = $name;
            $this->columnNameMap[$name] = $index;
            $this->columnTypeMap[$name] = $type;
        }
    }

    /**
     * Collect all column names, this to be called only when necessary.
     *
     * Using PDO, for example, it will do an extra round trip with the server per column.
     */
    private function collectAllColumnInfo()
    {
        if (!$this->everythingCollected) {
            for ($i = 0; $i < $this->countColumns(); ++$i) {
                if (!isset($this->loadedColumns[$i])) {
                    $this->collectColumnInfo($i);
                }
            }
            $this->everythingCollected = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countColumns(): int
    {
        return $this->columnCount ?? ($this->columnCount = $this->countColumnsFromDriver());
    }

    /**
     * Get column type
     */
    public function getColumnType(string $name): string
    {
        if (isset($this->userTypeMap[$name])) {
            return $this->userTypeMap[$name];
        }

        $this->collectAllColumnInfo();

        if (isset($this->columnTypeMap[$name])) {
            return $this->columnTypeMap[$name];
        }

        throw new QueryError(\sprintf("column '%s' does not exist in result", $name));
    }

    /**
     * {@inheritdoc}
     */
    public function setTypeMap(array $map): ResultIterator
    {
        $this->userTypeMap = $map;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function columnExists(string $name): bool
    {
        $this->collectAllColumnInfo();

        return isset($this->columnNameMap[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames(): array
    {
        $this->collectAllColumnInfo();

        return \array_flip($this->columnNameMap);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(int $index): string
    {
        if (!\is_int($index)) {
            throw new InvalidDataAccessError(\sprintf("'%s' is not an integer.\n", $index));
        }
        if ($index < 0) {
            throw new InvalidDataAccessError(\sprintf("Column count start with 0: %d given.\n", $index));
        }
        if (($count = $this->countColumns()) < $index + 1) {
            throw new InvalidDataAccessError(\sprintf("Column count is %d: %d given.\n", $count, $index));
        }

        if (!isset($this->loadedColumns[$index])) {
            $this->collectColumnInfo($index);
        }

        return $this->loadedColumns[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function fetchField($name = null)
    {
        foreach ($this as $row) {
            if ($name) {
                if (!\array_key_exists($name, $row)) {
                    throw new QueryError(\sprintf("column '%s' does not exist in result", $name));
                }
                return $row[$name];
            }
            return \reset($row);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->countRows();
    }
}
