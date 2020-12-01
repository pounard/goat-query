<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Query\QueryError;
use Goat\Runner\Hydrator\ResultHydrator;
use Goat\Runner\Metadata\DefaultResultMetadata;
use Goat\Runner\Metadata\ResultMetadata;
use Goat\Converter\ConverterContext;

abstract class AbstractResultIterator implements ResultIterator, \Iterator
{
    use WithQueryProfilerTrait;

    // Result information and configuration.
    private ?int $columnCount = null;
    private ?int $rowCount = null;
    private bool $debug = false;
    protected ?string $columnKey = null;

    // Meta-information and profiling information.
    private ?ResultMetadata $metadata = null;
    /** @var string[] */
    private array $userTypeMap = [];

    // Object hydration and value convertion.
    protected ?ConverterContext $context = null;
    private ?ResultHydrator $hydrator = null;

    // Iterator properties.
    private bool $iterationStarted = false;
    private bool $iterationCompleted = false;
    private bool $justRewinded = false;
    private bool $rewindable = false;
    private int $currentIndex = -1;

    // Current iterator item and iterator data.
    /** @var null|array|ResultIteratorItem[] */
    private ?array $expandedResult = null;
    /** @var null|mixed */
    private $currentValue = null;
    /** @var null|int|string */
    private $currentKey = null;

    /**
     * Implementation of both getColumnType() and getColumnName().
     *
     * @param int $index
     *
     * @return string[]
     *   First value must be column name, second column type
     */
    abstract protected function doFetchColumnInfoFromDriver(int $index): array;

    /**
     * Real implementation of getColumnName().
     */
    abstract protected function doFetchColumnsCountFromDriver(): int;

    /**
     * Get the driver result iterator, it must iterate over array of string values.
     */
    abstract protected function doFetchNextRowFromDriver(): ?array;

    /**
     * Fetch row count from driver result.
     */
    abstract protected function doFetchRowCountFromDriver(): int;

    /**
     * Free driver result if any left in memory.
     */
    abstract protected function doFreeResult(): void;

    /**
     * Was result previously free'd?
     */
    abstract protected function wasResultFreed(): bool;

    /**
     * Destruct result upon free.
     */
    public function __destruct()
    {
        $this->doFreeResult();
    }

    /**
     * Collect all column names.
     *
     * Tthis to be called only when necessary. Using PDO, for example, it will
     * do an extra round trip with the server per column for which we want to
     * fetch metadata (it's even worse when using pdo-pgsql because it will
     * extensively SELECT in pgsql information schema tables).
     *
     * In all cases, I deeple recommend using ext-pgsql implementation instead
     * which is much, much more efficient.
     */
    private function createMetadata(): ResultMetadata
    {
        $ret = new DefaultResultMetadata([], []);

        $count = $this->countColumns();

        for ($i = 0; $i < $count; ++$i) {
            list($name, $type) = $this->doFetchColumnInfoFromDriver($i);
            $ret->setColumnInformation($i, $name, $type);
        }

        return $ret;
    }

    /**
     * Get metadata instance.
     */
    final protected function getMetadata(): ResultMetadata
    {
        return $this->metadata ?? (
            $this->metadata = $this->createMetadata()
        );
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
    public function setRewindable($rewindable = true): ResultIterator
    {
        if ($this->iterationStarted) {
            throw new LockedResultError();
        }
        $this->rewindable = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setConverterContext(ConverterContext $context): ResultIterator
    {
        $this->context = $context;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function setHydrator($hydrator): ResultIterator
    {
        if ($this->iterationStarted) {
            throw new LockedResultError();
        }
        if (!$hydrator instanceof ResultHydrator) {
            $hydrator = new ResultHydrator($hydrator);
        }
        $this->hydrator = $hydrator;

        return $this;
    }

    /**
     * Convert a single value
     *
     * @param string $name
     * @param mixed $value
     *
     * @return mixed
     */
    final protected function convertValue(string $name, $value)
    {
        if (!$this->iterationStarted) {
            $this->iterationStarted = true;
        }
        if ($this->context) {
            return $this->context->getConverter()->fromSQL($this->getColumnType($name), $value, $this->context);
        }
        return $value;
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
    final protected function hydrate(array $row)
    {
        $ret = [];
        if ($this->context) {
            $converter = $this->context->getConverter();

            foreach ($row as $name => $value) {
                $name = (string)$name; // Column name can be an integer (eg. SELECT 1 ...).
                if (null !== $value) {
                    $ret[$name] = $converter->fromSQL($this->getColumnType($name), $value, $this->context);
                } else {
                    $ret[$name] = null;
                }
            }
        } else {
            foreach ($row as $name => $value) {
                $ret[(string)$name] = $value;
            }
        }

        if (!$this->hydrator) {
            return $ret;
        }

        return $this->hydrator->hydrate($ret);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->currentValue;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        if (!$this->iterationStarted) {
            $this->iterationStarted = true;
        }

        if (0 === $this->countRows()) {
            return;
        }

        ++$this->currentIndex;

        if ($this->rewindable) {
            // Iterator could have been rewinded before we reached the end
            // of the result, allow resuming from already expanded result
            // in order to ensure we will return the same hydrated object
            // instances, instead of creating new ones.
            $expanded = $this->expandedResult[$this->currentIndex] ?? null;

            if ($expanded) {
                $this->currentKey = $expanded->key;
                $this->currentValue = $expanded->value;

                return;
            }

            // If we completed iteration at least once, and current position
            // expanded result does not exists, then we reached the end, just
            // return;
            if ($this->iterationCompleted) {
                $this->currentKey = null;
                $this->currentValue = null;

                return;
            }
        } else if ($this->iterationCompleted) {
            // Avoid fetch call attempt if we already have fetched everything.
            // This is in order to avoid trying to call fetch() with a free'ed
            // result, this means that we do trust the underlaying connector
            // to give a coherent row count.
            $this->currentKey = null;
            $this->currentValue = null;

            return;
        }

        $row = $this->doFetchNextRowFromDriver();

        if (null === $row) {
            $this->iterationCompleted = true;
            $this->currentKey = null;
            $this->currentValue = null;

            return;
        }

        if ($this->columnKey) {
            $key = $row[$this->columnKey];
        } else {
            $key = $this->currentIndex;
        }

        $this->currentKey = $key;
        $this->currentValue = $this->hydrate($row);

        if ($this->rewindable) {
            // While iterating, we store key with the value, and not as being
            // an array key, because it's possible for us to iterate more than
            // once using the key, if there are same values in SQL result.
            $this->expandedResult[$this->currentIndex] = new ResultIteratorItem($this->currentKey, $this->currentValue);
        }

        if ($this->currentIndex === $this->countRows() - 1) {
            $this->doFreeResult();
            $this->iterationCompleted = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->currentKey;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return null !== $this->currentValue;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->currentIndex = -1;
        $this->currentKey = null;
        $this->currentValue = null;

        $this->next();

        $this->justRewinded = true;
    }

    /**
     * {@inheritdoc}
     */
    final public function fetchField($name = null)
    {
        if (null !== $this->hydrator) {
            throw new InvalidDataAccessError("You cannot call fetchField() if an hydrator is set.");
        }

        $this->next();

        $row = $this->current();

        if (null === $row) {
            return null;
        }

        if ($name) {
            if (!\array_key_exists($name, $row)) {
                throw new QueryError(\sprintf("column '%s' does not exist in result", $name));
            }

            return $row[$name];
        }

        return \reset($row);
    }

    /**
     * {@inheritdoc}
     */
    final public function fetch()
    {
        if ($this->justRewinded) {
            $this->justRewinded = false;
        } else {
            $this->next();
        }

        return $this->current();
    }

    /**
     * {@inheritdoc}
     */
    final public function setKeyColumn(string $name): ResultIterator
    {
        // Let it pass until iteration silently when not in debug mode.
        if ($this->debug && !$this->columnExists($name)) {
            throw new QueryError(\sprintf("column '%s' does not exist in result", $name));
        }

        $this->columnKey = $name;

        return $this;
    }

    /**
     * Get column type
     */
    final public function getColumnType(string $name): ?string
    {
        if (isset($this->userTypeMap[$name])) {
            return $this->userTypeMap[$name];
        }

        $type = $this->getMetadata()->getColumnType($name);

        if (null === $type && $this->debug) {
            throw new QueryError(\sprintf("column '%s' does not exist in result", $name));
        }

        return $type ?? 'varchar'; // Stupid but will never fail at conversion time.
    }

    /**
     * {@inheritdoc}
     */
    final public function setMetadata(array $userTypes, ?ResultMetadata $metadata = null): ResultIterator
    {
        if ($this->metadata) {
            if ($this->debug) {
                throw new InvalidDataAccessError("Result iterator metadata has already set.");
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
    final public function columnExists(string $name): bool
    {
        // Avoid metadata collection at all cost.
        if (isset($this->userTypeMap[$name])) {
            return true;
        }

        return $this->getMetadata()->columnExists($name);
    }

    /**
     * {@inheritdoc}
     */
    final public function getColumnNumber(string $name): int
    {
        return $this->getMetadata()->getColumnNumber($name);
    }

    /**
     * {@inheritdoc}
     */
    final public function getColumnNames(): array
    {
        return $this->getMetadata()->getColumnNames();
    }

    /**
     * {@inheritdoc}
     */
    final public function getColumnTypes(): array
    {
        return $this->getMetadata()->getColumnTypes();
    }

    /**
     * {@inheritdoc}
     */
    final public function getColumnName(int $index): string
    {
        return $this->getMetadata()->getColumnName($index);
    }

    /**
     * {@inheritdoc}
     */
    final public function countColumns(): int
    {
        return $this->columnCount ?? ($this->columnCount = $this->doFetchColumnsCountFromDriver());
    }

    /**
     * {@inheritdoc}
     */
    final public function countRows(): int
    {
        return $this->rowCount ?? ($this->rowCount = $this->doFetchRowCountFromDriver());
    }

    /**
     * {@inheritdoc}
     */
    final public function count()
    {
        return $this->rowCount ?? ($this->rowCount = $this->doFetchRowCountFromDriver());
    }
}

/**
 * @internal
 */
final class ResultIteratorItem
{
    public $key;
    public $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}

