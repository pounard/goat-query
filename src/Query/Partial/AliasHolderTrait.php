<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Query\Expression;
use Goat\Query\QueryError;
use Goat\Query\Expression\AliasedExpression;
use Goat\Query\Expression\TableExpression;

/**
 * Aliasing and conflict deduplication logic.
 */
trait AliasHolderTrait
{
    private int $aliasIndex = 0;
    /** @var array<string,Expression> */
    private array $tableIndex = [];

    /**
     * Normalize table to an expression with a given or generated alias.
     *
     * @param string|Expression $table
     *   A table name, a local alias, or an arbitrary expression instance.
     */
    protected function normalizeTable($table, ?string $alias = null): Expression
    {
        if ($table instanceof TableExpression) {
            $tableAlias = $table->getAlias();
            if ($schema = $table->getSchema()) {
                $tableName = $schema . '.' . $table->getName();
            } else {
                $tableName = $table->getName();
            }

            $alias = $this->createAliasForName($tableName, $alias ?? $tableAlias);

            return TableExpression::create($tableName, $alias, $table->getSchema());
        }

        if ($table instanceof Expression) {
            $expressionAlias = null;
            if ($table instanceof WithAlias) {
                $expressionAlias = $table->getAlias();
            }
            // Name needs to be unique, we will lookup for table names.
            $expressionName = '<nested raw expression ' . \uniqid(null, true) . '>';

            $alias = $this->createAliasForName($expressionName, $alias ?? $expressionAlias);

            return new AliasedExpression($alias, $table);
        }

        if (\is_string($table)) {
            return TableExpression::create($table, $this->createAliasForName($table, $alias));
        }

        throw new QueryError(\sprintf("\$table must be a string or an instance of %s", Expression::class));
    }

    /**
     * Normalize table to an table expression with a given or generated alias.
     *
     * @param string|TableExpression $table
     *   A table name, a local alias, or an arbitrary expression instance.
     */
    protected function normalizeStrictTable($table, ?string $alias = null): Expression
    {
        if ($table instanceof TableExpression) {
            $tableAlias = $table->getAlias();
            if ($schema = $table->getSchema()) {
                $tableName = $schema . '.' . $table->getName();
            } else {
                $tableName = $table->getName();
            }

            $alias = $this->createAliasForName($tableName, $alias ?? $tableAlias);

            return TableExpression::create($tableName, $alias, $table->getSchema());
        }

        if (\is_string($table)) {
            return TableExpression::create($table, $this->createAliasForName($table, $alias));
        }

        throw new QueryError(\sprintf("\$table must be a string or an instance of %s", TableExpression::class));
    }

    protected function createArbitraryAlias(string $name): string
    {
        $alias = 'goat_' . ++$this->aliasIndex;
        $this->tableIndex[$alias] = $name;

        return $alias;
    }

    protected function createAliasForName(string $name, ?string $alias = null): string
    {
        if ($alias) {
            if (isset($this->tableIndex[$alias])) {
                throw new QueryError(\sprintf(
                    "Alias '%s' is already registered for table '%s'",
                    $alias,
                    $this->tableIndex[$alias]
                ));
            }

            $this->tableIndex[$alias] = $name;

            return $alias;
        }

        // Avoid conflicting table names.
        if (false === \array_search($name, $this->tableIndex)) {
            $this->tableIndex[$name] = $name;

            return $name;
        }

        // Worst case scenario, we have to create an arbitry alias that the
        // user cannot guess, but which will prevent conflicts.
        return $this->createArbitraryAlias($name);
    }

    protected function removeAlias(string $alias): void
    {
        unset($this->tableIndex[$alias]);
    }
}
