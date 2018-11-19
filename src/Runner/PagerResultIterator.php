<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Converter\ConverterInterface;
use Goat\Hydrator\HydratorInterface;
use Goat\Query\QueryError;

/**
 * Wraps a result iterator in order to paginate results
 */
final class PagerResultIterator implements ResultIterator
{
    private $result;
    private $count = 0;
    private $limit = 0;
    private $page = 0;

    /**
     * Default constructor
     *
     * @param ResultIterator $result
     * @param int $count
     *   Total number of results.
     * @param int $limit
     *   Results per page.
     * @param int $page
     *   Current page number (starts at 1).
     */
    public function __construct(ResultIterator $result, int $count, int $limit, int $page)
    {
        if ($page < 1) {
            throw new QueryError(\sprintf("page numbering starts with 1, %d given", $page));
        }

        $this->count = $count;
        $this->limit = $limit;
        $this->page = $page;
        $this->result = $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter): void
    {
        $this->result->setConverter($converter);
    }

    /**
     * {@inheritdoc}
     */
    public function setHydrator(HydratorInterface $hydrator): void
    {
        $this->result->setHydrator($hydrator);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->result->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->result;
    }

    /**
     * Get attached result iterator
     *
     * @return ResultIterator
     */
    public function getResult(): ResultIterator
    {
        return $this->result;
    }

    /**
     * {@inheritdoc}
     */
    public function setKeyColumn(string $name): ResultIterator
    {
        $this->result->setKeyColumn($name);

        return $this;
    }

    /**
     * Get the number of results in this page
     */
    public function getCurrentCount(): int
    {
        return $this->result->countRows();
    }

    /**
     * Get the index of the first element of this page
     */
    public function getStartOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    /**
     * Get the index of the last element of this page
     */
    public function getStopOffset(): int
    {
        $stopOffset = $this->getStartOffset() + $this->getCurrentCount();

        if ($this->count < $stopOffset) {
            $stopOffset = $this->count;
        }

        return $stopOffset;
    }

    /**
     * Get the last page number
     */
    public function getLastPage(): int
    {
        return (int)\max(1, \ceil($this->count / $this->limit));
    }

    /**
     * Get current page number
     */
    public function getCurrentPage(): int
    {
        return $this->page;
    }

    /**
     * Is there a next page
     */
    public function hasNextPage(): bool
    {
        return $this->page < $this->getLastPage();
    }

    /**
     * Is there a previous page
     */
    public function hasPreviousPage(): bool
    {
        return 1 < $this->page;
    }

    /**
     * Get the total number of results in all pages
     */
    public function getTotalCount(): int
    {
        return $this->count;
    }

    /**
     * Get maximum result per page.
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * {@inheritdoc}
     */
    public function countColumns(): int
    {
        return $this->result->countColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function countRows(): int
    {
        return $this->result->countRows();
    }

    /**
     * {@inheritdoc}
     */
    public function columnExists(string $name): bool
    {
        return $this->result->columnExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames(): array
    {
        return $this->result->getColumnNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType(string $name): string
    {
        return $this->result->getColumnType($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(int $index): string
    {
        return $this->result->getColumnName($index);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchField($name = null)
    {
        return $this->result->fetchField($name);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($name = null)
    {
        return $this->result->fetchColumn($name);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch()
    {
        return $this->result->fetch();
    }
}
