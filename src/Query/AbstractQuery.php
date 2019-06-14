<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Partial\AliasHolderTrait;
use Goat\Query\Partial\WithClauseTrait;
use Goat\Runner\Runner;
use Goat\Runner\ResultIterator;

abstract class AbstractQuery implements Query
{
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
     * {@inheritdoc}
     */
    final public function setRunner(Runner $runner): void
    {
        $this->runner = $runner;
    }

    /**
     * {@inheritdoc}
     */
    final public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * {@inheritdoc}
     */
    final public function setIdentifier(string $identifier): Query
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function getRelation(): ?ExpressionRelation
    {
        return $this->relation;
    }

    /**
     * {@inheritdoc}
     */
    final public function setOption(string $name, $value): Query
    {
        if (null === $value) {
            unset($this->options[$name]);
        } else {
            $this->options[$name] = $value;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function setOptions(array $options): Query
    {
        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    final public function execute($arguments = null, $options = null): ResultIterator
    {
        if (!$this->runner) {
            throw new QueryError("this query has no reference to query runner, therefore cannot execute");
        }

        return $this->runner->execute($this, $arguments, $this->getOptions($options));
    }

    /**
     * {@inheritdoc}
     */
    public function perform($arguments = null, $options = null): int
    {
        if (!$this->runner) {
            throw new QueryError("this query has no reference to any query runner, therefore cannot perform");
        }

        return $this->runner->perform($this, $arguments, $this->getOptions($options));
    }
}
