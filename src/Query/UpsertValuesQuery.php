<?php

declare(strict_types=1);

namespace Goat\Query;

use Goat\Query\Partial\InsertValuesTrait;
use Goat\Query\Partial\ReturningQueryTrait;
use Goat\Query\Partial\UpsertTrait;

/**
 * Represent either one of UPDATE .. ON CONFLICT DO .. or MERGE .. queries
 * depending upon your database implementation.
 *
 * One limitation is that this will not work with custom keys, it MUST be
 * primary key of the table, this is not a real MERGE statement but only
 * a very primitive use of it.
 *
 * Because standard SQL:2003 MERGE statement is not implemented everywhere
 * and each DBMS has its own variation, we only implement a tiny subset of
 * it, but we will try to make it pertinent and usable.
 *
 * Consider the following code:
 *
 * @code
 *   UpsertQuery('table')
 *       // Required only in case of onDuplicateUpdate().
 *       ->primaryKey(['foo', 'bar']) // Obligatoire si on duplicate update
 *       // Methods query() and values() are mutually exclusive.
 *       ->values([ ... ]) | ->query(new SelectQuery() ... ) 
 *       // Methods onDuplicateIgnore() and onDuplicateUpdate() are mutually
 *       // exclusive as well. For onDuplicateUpdate() column list is optional
 *       // and will reduce the columns to be updated if set.
 *       ->onDuplicateIgnore() | ->onDuplicateUpdate(['fizz', 'buzz'])
 *   ;
 * @endcode
 *
 * It will be converted to pgsql as such:
 * 
 * @code
 *   INSERT INTO table
 *       <VALUES OR SELECT>
 *   ON CONFLICT ('foo', 'bar')
 *       DO NOTHING | DO UPDATE SET fizz = EXCLUDED.fizz, buzz = EXCLUDED.buzz
 * @endcode
 *
 * Or in standard SQL with a MERGE statement:
 *
 * @code
 *   MERGE INTO table
 *   USING <VALUES OR SELECT> AS _table
 *   WHEN MATCHED THEN
 *       UPDATE table SET
 *           fizz = _table.fizz,
 *           buzz = _table.buzz
 *   WHEN NOT MATCHED
 *       INSERT INTO table (
 *           foo, bar, fizz, buzz
 *       ) VALUES (
 *           _table.foo, _table.bar, _table.fizz, _table.buzz
 *       )
 *   ;
 * @endcode
 *
 * In both case <VALUES OR SELECT> can be either a subquery, or a constant
 * table expression (ie. VALUES (), (), ...).
 *
 * Also, PostgreSQL support more than one ON CONFLICT () statement, which makes
 * it much more flexible than standard MERGE, but we will not support that for
 * now.
 */
final class UpsertValuesQuery extends AbstractQuery
{
    use InsertValuesTrait;
    use ReturningQueryTrait;
    use UpsertTrait;

    /**
     * {@inheritdoc}
     */
    public function getArguments(): ArgumentBag
    {
        $arguments = new ArgumentBag();

        foreach ($this->getAllWith() as $selectQuery) {
            $arguments->append($selectQuery[1]->getArguments());
        }

        $arguments->append($this->arguments);

        return $arguments;
    }
}
