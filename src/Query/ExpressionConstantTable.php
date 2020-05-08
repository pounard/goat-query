<?php

declare(strict_types=1);

namespace Goat\Query;

/**
 * Constant table expression reprense one or more rows of raw arbitrary
 * values, them that you would write in INSERT or MERGE queries after the
 * VALUES () keyword.
 *
 * PostgreSQL among others allows to use VALUES (...) [, ...] expressions
 * in place of tables in queries. This class allows you to build such
 * expressions.
 */
final class ExpressionConstantTable implements Expression
{
    private $arguments;
    private $columnCount = 0;
    private $valueCount = 0;
    private $valueInitialized = false;

    private function __construct()
    {
        $this->arguments = new ArgumentBag();
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * Get column count.
     */
    public function getColumnCount(): int
    {
        return $this->columnCount;
    }

    /**
     * Get value count.
     */
    public function getValueCount(): int
    {
        return $this->valueCount;
    }

    /**
     * Add an arbitrary set of values.
     *
     * First call determines the column count, subsequent calls are checked
     * upon the column count and will raise error in case of count mismatch.
     */
    public function values(array $values): self
    {
        if ($this->valueInitialized) {
            if (\count($values) !== $this->columnCount) {
                throw new QueryError("values count does not match previous value count");
            }
        } else {
            $this->valueInitialized = true;
            $this->columnCount = \count($values);
        }

        foreach ($values as $value) {
            $this->arguments->add($value);
        }

        $this->valueCount++;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        return $this->arguments;
    }
}
