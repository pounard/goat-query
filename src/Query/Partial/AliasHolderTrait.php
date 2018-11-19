<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\ExpressionRelation;
use Goat\Query\QueryError;

/**
 * Aliasing and conflict dedupe logic.
 */
trait AliasHolderTrait
{
    private $aliasIndex = 0;
    private $relationIndex = [];

    /**
     * Normalize relation reference
     *
     * @param string|ExpressionRelation $relation
     * @param string $alias
     */
    protected function normalizeRelation($relation, ?string $alias = null): ExpressionRelation
    {
        if ($relation instanceof ExpressionRelation) {
            if ($relation->getAlias() && $alias) {
                throw new QueryError(\sprintf(
                    "relation %s is already prefixed by %s, conflicts with %s",
                    $relation->getName(),
                    $relation->getAlias(),
                    $alias
                ));
            }
        } else {
            if (null === $alias) {
                $alias = $this->getAliasFor($relation);
            } else {
                if ($this->aliasExists($alias)) {
                    throw new QueryError(\sprintf("%s alias is already registered for relation %s", $alias, $this->relations[$alias]));
                }
            }

            $relation = ExpressionRelation::create($relation, $alias);
        }

        return $relation;
    }

    /**
     * Get alias for relation, if none registered add a new one
     *
     * @param string $relationName
     * @param string $userAlias
     *   Existing alias if any
     */
    protected function getAliasFor(string $relationName, ?string $userAlias = null): string
    {
        if ($userAlias) {
            if (isset($this->relationIndex[$userAlias])) {
                throw new QueryError(
                    \sprintf(
                        "cannot use alias %s for relation %s, already in use for table %s",
                        $userAlias,
                        $relationName,
                        $this->relationIndex[$userAlias]
                    )
                );
            } else {
                $this->relationIndex[$userAlias] = $relationName;

                return $userAlias;
            }
        }

        $index = \array_search($relationName, $this->relationIndex);

        if (false !== $index) {
            $alias = 'goat_' . ++$this->aliasIndex;
        } else {
            $alias = $relationName;
        }

        $this->relationIndex[$alias] = $relationName;

        return $alias;
    }

    /**
     * Remove alias
     */
    protected function removeAlias(string $alias): void
    {
        unset($this->relationIndex[$alias]);
    }

    /**
     * Does alias exists
     */
    protected function aliasExists(string $alias): bool
    {
        return isset($this->relationIndex[$alias]);
    }
}
