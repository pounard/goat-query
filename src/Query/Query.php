<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Partial\AliasHolderTrait;
use Goat\Query\Partial\WithClauseTrait;
use Goat\Runner\Runner;
use Goat\Runner\ResultIterator;

abstract class Query implements Statement
{
    const JOIN_INNER = 4;
    const JOIN_LEFT = 2;
    const JOIN_LEFT_OUTER = 3;
    const JOIN_RIGHT = 5;
    const JOIN_RIGHT_OUTER = 6;
    const JOIN_NATURAL = 1;
    const NULL_FIRST = 2;
    const NULL_IGNORE = 0;
    const NULL_LAST = 1;
    const ORDER_ASC = 1;
    const ORDER_DESC = 2;

    use AliasHolderTrait;
    use WithClauseTrait;

    private $identifier;
    private $options = [];
    private $relation;
    private $runner;

    /**
     * Build a new query
     *
     * @param null|string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     */
    public function __construct($relation = null, ?string $alias = null)
    {
        if ($relation) {
            $this->relation = $this->normalizeRelation($relation, $alias);
        }
    }

    /**
     * Set runner
     *
     * @internal
     */
    final public function setRunner(Runner $runner): void
    {
        $this->runner = $runner;
    }

    /**
     * Get query identifier
     */
    final public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * Set query unique identifier
     */
    final public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get SQL from relation
     */
    final public function getRelation(): ?ExpressionRelation
    {
        return $this->relation;
    }

    /**
     * Set a single query options
     *
     * null value means reset to default.
     */
    final public function setOption(string $name, $value): self
    {
        if (null === $value) {
            unset($this->options[$name]);
        } else {
            $this->options[$name] = $value;
        }

        return $this;
    }

    /**
     * Set all options from
     *
     * null value means reset to default.
     */
    final public function setOptions(array $options): self
    {
        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }

        return $this;
    }

    /**
     * Get normalized options
     *
     * @param null|string|array
     *
     * @return array
     */
    final public function getOptions($overrides = null): array
    {
        if ($overrides) {
            if (!\is_array($overrides)) {
                $overrides = ['class' => $overrides];
            }
            $options = \array_merge($this->options, $overrides);
        } else {
            $options = $this->options;
        }

        return $options;
    }

    /**
     * Execute query with the given parameters and return the result iterator
     *
     * @param mixed[] $arguments
     *   Parameters or overrides for the query. When a Query instance is given
     *   as query and it carries parameters, this array will serve as a set of
     *   overrides for existing parameters.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return ResultIterator
     */
    final public function execute($arguments = null, $options = null): ResultIterator
    {
        if (!$this->runner) {
            throw new QueryError("this query has no reference to query runner, therefore cannot execute");
        }

        return $this->runner->execute($this, $arguments, $this->getOptions($options));
    }

    /**
     * Execute query with the given parameters and return the affected row count
     *
     * @param mixed[] $arguments
     *   Parameters or overrides for the query. When a Query instance is given
     *   as query and it carries parameters, this array will serve as a set of
     *   overrides for existing parameters.
     * @param string|mixed[] $options
     *   If a string is passed, map object on the given class, else parse
     *   query options and set them onto the result iterator.
     *
     * @return int
     */
    public function perform($arguments = null, $options = null): int
    {
        if (!$this->runner) {
            throw new QueryError("this query has no reference to any query runner, therefore cannot perform");
        }

        return $this->runner->perform($this, $arguments, $this->getOptions($options));
    }

    /**
     * Should this query return something
     *
     * For INSERT, MERGE, UPDATE or DELETE queries without a RETURNING clause
     * this should return false, same goes for PostgresSQL PERFORM.
     *
     * Note that SELECT queries might also be run with a PERFORM returning
     * nothing, for example in some cases with FOR UPDATE.
     *
     * This may trigger some optimizations, for example with PDO this will
     * force the RETURN_AFFECTED behavior.
     *
     * @return bool
     */
    public abstract function willReturnRows(): bool;
}
