<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\Query;
use Goat\Query\QueryError;

/**
 * Common data for INSERT ON CONCLIT or MERGE queries.
 */
trait UpsertTrait
{
    private $conflictBehaviour = Query::CONFLICT_UPDATE;
    private $primaryKey = [];

    /**
     * Use this only if your RDBMS supports standard SQL:2003 MERGE query,
     * this sets the USING clause relation alias.
     *
     * If you don't set one, one will be generated for you.
     */
    public function setUsingRelationAlias(string $alias): self
    {
        $this->usingRelationAlias = $alias;
    }

    /**
     * Get using relation alias.
     */
    public function getUsingRelationAlias(): string
    {
        return $this->usingRelationAlias ?? (
            $this->usingRelationAlias = $this->getAliasFor('upsert')
        );
    }

    /**
     * Set upsert key (primary key for matching for conflict).
     */
    public function setKey(array $columnNames): self
    {
        foreach ($columnNames as $columnName) {
            if (!\is_string($columnName) || false !== \strpos($columnName, '.')) {
                throw new QueryError("column names in the primary key of an upsert query can only be a column name, without table prefix");
            }
        }

        $this->primaryKey = $columnNames;

        return $this;
    }

    /**
     * Get upsert key (primary key for matching for conflict).
     */
    public function getKey(): array
    {
        return $this->primaryKey;
    }

    /**
     * Set manually on conflict behaviour.
     */
    public function onConflict(int $mode): self
    {
        if (Query::CONFLICT_IGNORE !== $mode && Query::CONFLICT_UPDATE !== $mode) {
            throw new QueryError(\sprintf(
                "ON CONFLICT | WHEN [NOT] MATCHED behaviours must be one of %s::CONFLICT_IGNORE or %s::CONFLICT_UPDATE",
                Query::class, Query::class
            ));
        }

        $this->conflictBehaviour = $mode;

        return $this;
    }

    /**
     * Ignore conflicting rows.
     */
    public function onConflictIgnore(): self
    {
        $this->onConflict(Query::CONFLICT_IGNORE);

        return $this;
    }

    /**
     * Update conflicting rows.
     */
    public function onConflictUpdate(): self
    {
        $this->onConflict(Query::CONFLICT_UPDATE);

        return $this;
    }

    /**
     * Get conflict behaviour.
     */
    public function getConflictBehaviour(): int
    {
        return $this->conflictBehaviour;
    }
}
