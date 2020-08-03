<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Query\QueryError;

/**
 * Wraps a result iterator in order to paginate results
 */
final class PagerResultIterator extends AbstractResultIteratorProxy
{
    private int $count = 0;
    private int $limit = 0;
    private int $page = 0;
    private ResultIterator $result;

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
        if ($limit < 0) {
            throw new QueryError(\sprintf("limit starts with 0, %d given", $page));
        }

        $this->count = $count;
        $this->limit = $limit;
        $this->page = $page;
        $this->result = $result;
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
     * Get the number of results in this page
     */
    public function getCurrentCount(): int
    {
        return $this->getResult()->countRows();
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
        $totalCount = $this->getTotalCount() ?? 0;

        return ($totalCount < $stopOffset) ? $totalCount : $stopOffset;
    }

    /**
     * Get the last page number
     */
    public function getLastPage(): int
    {
        return $this->limit ? (int)\max(1, \ceil(($this->getTotalCount() ?? 0) / $this->limit)) : 1;
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
    public function getTotalCount(): ?int
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
}
