<?php

declare(strict_types=1);

namespace Goat\Runner;

use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;

/**
 * Wraps a query in order to paginate results
 */
final class QueryPagerResultIterator extends AbstractResultIteratorProxy
{
    private ?Query $countQuery = null;
    private SelectQuery $query;
    private int $limit = 0;
    private int $page = 1;
    private ?int $totalCount = null;
    private ?array $queryArguments = null;
    private ?array $queryOptions = null;
    private ?ResultIterator $result = null;

    /**
     * Default constructor
     */
    public function __construct(SelectQuery $query, ?Query $countQuery = null)
    {
        $this->countQuery = $countQuery;
        $this->query = $query;
    }

    /**
     * Set query option
     */
    public function setQueryOptions($options): self
    {
        if ($this->result) {
            throw new QueryError("Query has already been executed");
        }

        $this->queryOptions = $options;

        return $this;
    }

    /**
     * Set query arguments
     */
    public function setQueryArguments(?array $arguments): self
    {
        if ($this->result) {
            throw new QueryError("Query has already been executed");
        }

        $this->queryArguments = $arguments;

        return $this;
    }

    /**
     * Set page
     */
    public function setPage(int $page): self
    {
        if ($this->result) {
            throw new QueryError("Query has already been executed");
        }
        if ($page < 1) {
            throw new QueryError("Page must be greater or equal to 1");
        }

        $this->page = $page;

        return $this;
    }

    /**
     * Set limit, use 0 for no limit
     */
    public function setLimit(int $limit): self
    {
        if ($this->result) {
            throw new QueryError("Query has already been executed");
        }
        if ($limit < 0) {
            throw new QueryError("Limit must be greater or equal to 0");
        }

        $this->limit = $limit;

        return $this;
    }

    /**
     * Execute query
     */
    private function executeQuery(): ResultIterator
    {
        return $this->result ?? (
            $this->result = $this
                ->query
                ->range($this->limit, $this->getStartOffset())
                ->execute($this->queryArguments, $this->queryOptions)
        );
    }

    /**
     * Get attached result iterator
     *
     * @return ResultIterator
     */
    public function getResult(): ResultIterator
    {
        return $this->result ?? ($this->result = $this->executeQuery());
    }

    /**
     * Get the number of results in this page
     */
    public function getCurrentCount(): int
    {
        return $this->count();
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
        $totalCount = $this->getTotalCount();

        return ($totalCount < $stopOffset) ? $totalCount : $stopOffset;
    }

    /**
     * Get the last page number
     */
    public function getLastPage(): int
    {
        return $this->limit ? (int)\max(1, \ceil($this->getTotalCount() / $this->limit)) : 1;
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
        return $this->totalCount ?? (
            $this->totalCount = ($this->countQuery ?? $this->query->getCountQuery())
                ->execute($this->queryArguments, $this->queryOptions)
                ->fetchField()
        );
    }

    /**
     * Get maximum result per page.
     */
    public function getLimit(): int
    {
        return $this->limit;
    }
}
