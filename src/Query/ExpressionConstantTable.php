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
    private int $columnCount = 0;
    private int $rowCount = 0;
    private bool $rowsInitialized = false;
    /** @var ExpressionRow[] */
    private array $rows = [];

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
     * Get row count.
     */
    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * Get rows.
     */
    public function getRows(): iterable
    {
        return $this->rows;
    }

    /**
     * Add an arbitrary set of values.
     *
     * First call determines the column count, subsequent calls are checked
     * upon the column count and will raise error in case of count mismatch.
     *
     * @param iterable|ExpressionRow $row
     */
    public function row($row): self
    {
        if (!$row instanceof ExpressionRow) {
            if (!\is_iterable($row)) {
                throw new QueryError(\sprintf("Values must be an iterable or an %s instance", ExpressionRow::class));
            }

            $row = ExpressionRow::create($row);
        }

        $columnCount = $row->getColumnCount();

        if ($this->rowsInitialized) {
            if ($columnCount !== $this->columnCount) {
                throw new QueryError(\sprintf("Value count %d does not match previous value count %d", $columnCount, $this->columnCount));
            }
        } else {
            $this->rowsInitialized = true;
            $this->columnCount = $columnCount;
        }

        $this->rows[] = $row;
        $this->rowCount++;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        $ret = new ArgumentBag();

        foreach ($this->rows as $value) {
            \assert($value instanceof Statement);

            $ret->append($value->getArguments());
        }

        return $ret;
    }
}
