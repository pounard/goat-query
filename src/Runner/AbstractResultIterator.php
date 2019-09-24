<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Hydrator\HydratorInterface;
use Goat\Query\QueryError;
use Goat\Runner\Metadata\DefaultResultMetadata;
use Goat\Runner\Metadata\ResultMetadata;

abstract class AbstractResultIterator implements ResultIterator
{
    /** @var ?int */
    private $columnCount;

    /** @var bool */
    private $debug = false;

    /** @var ResultMetadata */
    private $metadata;

    /** @var string[] */
    private $userTypeMap = [];

    /** @var ?string */
    protected $columnKey;

    /** @var ?ConverterInterface */
    protected $converter;

    /** @var ?HydratorInterface */
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
     * Collect all column names, this to be called only when necessary.
     *
     * Using PDO, for example, it will do an extra round trip with the server per column.
     */
    private function collectAllColumnInfo()
    {
        if (!$this->metadata) {
            $this->metadata = new DefaultResultMetadata([], []);
            $count = $this->countColumns();
            for ($i = 0; $i < $count; ++$i) {
                list($name, $type) = $this->getColumnInfoFromDriver($i);
                $this->metadata->setColumnInformation($i, $name, $type);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug(bool $enable): void
    {
        $this->debug = $enable;
    }

    /**
     * Is debug mode enabled.
     */
    protected function isDebugEnabled(): bool
    {
        return $this->debug;
    }

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

        if ($this->debug) {
            \trigger_error("result iterator has no converter set", E_USER_WARNING);
        }

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
        if (!$this->converter && $this->debug) {
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
    public function countColumns(): int
    {
        return $this->columnCount ?? ($this->columnCount = $this->countColumnsFromDriver());
    }

    /**
     * {@inheritdoc}
     */
    public function setKeyColumn(string $name): ResultIterator
    {
        // Let it pass until iteration silently when not in debug mode
        if ($this->debug && !$this->columnExists($name)) {
            throw new QueryError(\sprintf("column '%s' does not exist in result", $name));
        }

        $this->columnKey = $name;

        return $this;
    }

    /**
     * Get column type
     */
    public function getColumnType(string $name): ?string
    {
        if (isset($this->userTypeMap[$name])) {
            return $this->userTypeMap[$name];
        }

        if (!$this->metadata) {
            $this->collectAllColumnInfo();
        }

        $type = $this->metadata->getColumnType($name);

        if (null === $type && $this->debug) {
            throw new QueryError(\sprintf("column '%s' does not exist in result", $name));
        }

        return $type ?? 'varchar'; // Stupid but will never fail at conversion time.
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata(array $userTypes, ?ResultMetadata $metadata = null): ResultIterator
    {
        if ($this->metadata) {
            if ($this->debug) {
                throw new InvalidDataAccessError("Result iterator metadata has already set");
            }

            return $this;
        }

        $this->metadata = $metadata;
        $this->userTypeMap = $userTypes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function columnExists(string $name): bool
    {
        // Avoid metadata collection at all cost.
        if (isset($this->userTypeMap[$name])) {
            return true;
        }

        if (!$this->metadata) {
            $this->collectAllColumnInfo();
        }

        return $this->metadata->columnExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNumber(string $name): int
    {
        if (!$this->metadata) {
            $this->collectAllColumnInfo();
        }

        return $this->metadata->getColumnNumber($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames(): array
    {
        if (!$this->metadata) {
            $this->collectAllColumnInfo();
        }

        return $this->metadata->getColumnNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnTypes(): array
    {
        if (!$this->metadata) {
            $this->collectAllColumnInfo();
        }

        return $this->metadata->getColumnTypes();
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(int $index): string
    {
        if (!$this->metadata) {
            $this->collectAllColumnInfo();
        }

        return $this->metadata->getColumnName($index);
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
