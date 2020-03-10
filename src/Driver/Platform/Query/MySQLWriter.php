<?php

declare(strict_types=1);

namespace Goat\Driver\Platform\Query;

use Goat\Driver\Query\DefaultSqlWriter;
use Goat\Query\DeleteQuery;
use Goat\Query\QueryError;
use Goat\Query\UpdateQuery;

/**
 * MySQL < 8
 */
class MySQLWriter extends DefaultSqlWriter
{
    /**
     * {@inheritdoc}
     */
    protected function getCastType(string $type) : string
    {
        $type = parent::getCastType($type);

        // Specific type conversion for MySQL because its CAST() function
        // does not accepts the same datatypes as the one it handles.
        if ('timestamp' === $type) {
            return 'datetime';
        } else if ('int' === \mb_substr($type, 0, 3)) {
            return 'signed integer';
        } else if ('float' === \mb_substr($type, 0, 5) || 'double' === \mb_substr($type, 0, 6)) {
            return 'decimal';
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    protected function writeCast(string $placeholder, string $type): string
    {
        // This is supposedly SQL-92 standard compliant, but can be overriden
        return \sprintf("cast(%s as %s)", $placeholder, $type);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatInsertNoValuesStatement() : string
    {
        return "() VALUES ()";
    }

    /**
     * {@inheritdoc}
     */
    protected function formatQueryDelete(DeleteQuery $query) : string
    {
        $output = [];

        // MySQL need to specify on which table to delete from if there is an
        // alias on the main table, so we are going to give him this always
        // so we won't have to bother about weither or not we have other tables
        // to JOIN.
        if (!$relation = $query->getRelation()) {
            throw new QueryError("delete query must have a relation");
        }

        $relationAlias = $relation->getAlias();
        if (!$relationAlias) {
            $relationAlias = $relation->getName();
        }

        $output[] = \sprintf(
            "delete %s.* from %s",
            $this->escaper->escapeIdentifier($relationAlias),
            $this->formatExpressionRelation($relation)
        );

        // MySQL does not have USING clause, and support a non-standard way of
        // writing DELETE directly using FROM .. JOIN clauses, just like you
        // would write a SELECT, so give him that.
        $joins = $query->getAllJoin();
        if ($joins) {
            $output[] = $this->formatJoin($joins);
        }

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = \sprintf('where %s', $this->formatWhere($where));
        }

        $return = $query->getAllReturn();
        if ($return) {
            throw new QueryError("MySQL does not support RETURNING SQL clause");
        }

        return \implode("\n", \array_filter($output));
    }

    /**
     * {@inheritdoc}
     */
    protected function formatQueryUpdate(UpdateQuery $query) : string
    {
        $output = [];

        $columns = $query->getUpdatedColumns();
        if (empty($columns)) {
            throw new QueryError("cannot run an update query without any columns to update");
        }

        // From the SQL 92 standard (which PostgreSQL does support here) the
        // FROM and JOIN must be written AFTER the SET clause. MySQL does not.
        if (!$relation = $query->getRelation()) {
            throw new QueryError("update query must have a relation");
        }
        $output[] = \sprintf("update %s", $this->formatExpressionRelation($relation));

        // MySQL don't do UPDATE t1 SET [...] FROM t2 but uses the SELECT
        // syntax and just append the set after the JOIN clause.
        $joins = $query->getAllJoin();
        if ($joins) {
            $output[] = $this->formatJoin($query->getAllJoin());
        }

        // SET clause.
        $output[] = \sprintf("set\n%s", $this->formatUpdateSet($columns));

        $where = $query->getWhere();
        if (!$where->isEmpty()) {
            $output[] = \sprintf('where %s', $this->formatWhere($where));
        }

        $return = $query->getAllReturn();
        if ($return) {
            throw new QueryError("MySQL does not support RETURNING SQL clause");
        }

        return \implode("\n", $output);
    }
}
