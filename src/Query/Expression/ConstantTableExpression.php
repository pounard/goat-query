<?php

declare(strict_types=1);

namespace Goat\Query\Expression;

use Goat\Query\Expression;
use Goat\Query\QueryError;

/**
 * Constant table expression reprensent one or more rows of raw arbitrary
 * values, them that you would write in INSERT or MERGE queries after the
 * VALUES () keyword.
 *
 * PostgreSQL among others allows to use VALUES (...) [, ...] expressions
 * in place of tables in queries. This class allows you to build such
 * expressions.
 */
class ConstantTableExpression implements Expression
{
    private int $columnCount = 0;
    private int $rowCount = 0;
    private bool $rowsInitialized = false;
    /** @var ConstantRowExpression[] */
    private array $rows = [];

    /**
     * Create a constant table expression.
     *
     * @param ?iterable $rows
     *   An array of iterables, whose are rows, each row must contain the same
     *   number of values.
     */
    public function __construct(?iterable $rows = null)
    {
        if ($rows) {
            foreach ($rows as $row) {
                $this->row($row);
            }
        }
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
     * @param iterable|ConstantRowExpression $row
     */
    public function row($row): self
    {
        if (!$row instanceof ConstantRowExpression) {
            if (!\is_iterable($row)) {
                throw new QueryError(\sprintf("Values must be an iterable or an %s instance", ConstantRowExpression::class));
            }

            $row = new ConstantRowExpression($row);
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
}
