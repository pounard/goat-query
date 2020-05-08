<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\MergeQuery;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use PHPUnit\Framework\TestCase;

final class MergeUnitTest extends TestCase
{
    use BuilderTestTrait;

    private function createUsingQuery(): Query
    {
        return (new SelectQuery('table2'))
            ->column('a')
            ->column('b')
            ->column('c')
            ->column('d')
        ;
    }

    public function testRaiseErrorIfValuesIsCalledAfterQuery(): void
    {
        $insert = (new MergeQuery('some_table'))
            ->columns(['pif', 'paf'])
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/mutually exclusive/');

        $insert->values(['foo', 'bar']);
    }

    public function testRaiseErrorIfQueryIsCalledAfterValues(): void
    {
        $insert = (new MergeQuery('some_table'))
            ->columns(['pif', 'paf'])
            ->values(['foo', 'bar'])
        ;

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/was already set/');

        $insert->query(
            $this->createUsingQuery()
        );
    }

    public function testStringWithDotKeyRaiseError(): void
    {
        $query = (new MergeQuery('table1'))
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/column names in the primary/');

        $query->setKey(['foo.bar']);
    }

    public function testNonStringKeyRaiseError(): void
    {
        $query = new MergeQuery('table1');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/column names in the primary/');

        $query->setKey([new \DateTimeImmutable()]);
    }

    public function testInvalidConflictBehaviourRaiseError(): void
    {
        $query = new MergeQuery('table1');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/behaviours must be one/');

        $query->onConflict(7);
    }

    public function testValueOnConflictIgnore(): void
    {
        $query = (new MergeQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictIgnore()
            ->values([1, 2, 3, 4])
            ->values([5, 6, 7, 8])
        ;

        self::assertSameSql(<<<SQL
merge into "table1"
using
    values (
        ?, ?, ?, ?
    ), (
        ?, ?, ?, ?
    ) as "upsert"
when not matched then
    insert into "table1" (
        "foo", "bar", "fizz", "buzz"
    ) values (
        "upsert"."foo",
        "upsert"."bar",
        "upsert"."fizz",
        "upsert"."buzz"
    )
SQL
            ,
            self::createStandardSqlWriter()->format($query)
        );
    }

    public function testValueOnConflictIgnoreIgnoresKey(): void
    {
        $query = (new MergeQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictIgnore()
            ->setKey(['foo', 'bar'])
            ->values([1, 2, 3, 4])
            ->values([5, 6, 7, 8])
        ;

        self::assertSameSql(<<<SQL
merge into "table1"
using
    values (
        ?, ?, ?, ?
    ), (
        ?, ?, ?, ?
    ) as "upsert"
when not matched then
    insert into "table1" (
        "foo", "bar", "fizz", "buzz"
    ) values (
        "upsert"."foo",
        "upsert"."bar",
        "upsert"."fizz",
        "upsert"."buzz"
    )
SQL
            ,
            self::createStandardSqlWriter()->format($query)
        );
    }

    public function testValueOnConflictUpdate(): void
    {
        $query = (new MergeQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictUpdate()
            ->setKey(['foo', 'bar'])
            ->values([1, 2, 3, 4])
            ->values([5, 6, 7, 8])
        ;

        self::assertSameSql(<<<SQL
merge into "table1"
using
    values (
        ?, ?, ?, ?
    ), (
        ?, ?, ?, ?
    ) as "upsert"
when matched then
    update set
        "fizz" = "upsert"."fizz",
        "buzz" = "upsert"."buzz"
when not matched then
    insert into "table1" (
        "foo", "bar", "fizz", "buzz"
    ) values (
        "upsert"."foo",
        "upsert"."bar",
        "upsert"."fizz",
        "upsert"."buzz"
    )
SQL
            ,
            self::createStandardSqlWriter()->format($query)
        );
    }

    public function testValueOnConflictUpdateWithoutKey(): void
    {
        $query = (new MergeQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictUpdate()
            ->values([1, 2, 3, 4])
            ->values([5, 6, 7, 8])
        ;

        self::assertSameSql(<<<SQL
merge into "table1"
using
    values (
        ?, ?, ?, ?
    ), (
        ?, ?, ?, ?
    ) as "upsert"
when matched then
    update set
        "foo" = "upsert"."foo",
        "bar" = "upsert"."bar",
        "fizz" = "upsert"."fizz",
        "buzz" = "upsert"."buzz"
when not matched then
    insert into "table1" (
        "foo", "bar", "fizz", "buzz"
    ) values (
        "upsert"."foo",
        "upsert"."bar",
        "upsert"."fizz",
        "upsert"."buzz"
    )
SQL
            ,
            self::createStandardSqlWriter()->format($query)
        );
    }

    public function testQueryOnConflictIgnore(): void
    {
        $query = (new MergeQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictIgnore()
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::assertSameSql(<<<SQL
merge into "table1"
using (
    select "a", "b", "c", "d" from "table2"
) as "upsert"
when not matched then
    insert into "table1" (
        "foo", "bar", "fizz", "buzz"
    ) values (
        "upsert"."foo",
        "upsert"."bar",
        "upsert"."fizz",
        "upsert"."buzz"
    )
SQL
            ,
            self::createStandardSqlWriter()->format($query)
        );
    }

    public function testQueryOnConflictIgnoreIgnoresKey(): void
    {
        $query = (new MergeQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictIgnore()
            ->setKey(['foo', 'bar'])
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::assertSameSql(<<<SQL
merge into "table1"
using (
    select "a", "b", "c", "d" from "table2"
) as "upsert"
when not matched then
    insert into "table1" (
        "foo", "bar", "fizz", "buzz"
    ) values (
        "upsert"."foo",
        "upsert"."bar",
        "upsert"."fizz",
        "upsert"."buzz"
    )
SQL
            ,
            self::createStandardSqlWriter()->format($query)
        );
    }

    public function testQueryOnConflictUpdate(): void
    {
        $query = (new MergeQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictUpdate()
            ->setKey(['foo', 'bar'])
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::assertSameSql(<<<SQL
merge into "table1"
using (
    select "a", "b", "c", "d" from "table2"
) as "upsert"
when matched then
    update set
        "fizz" = "upsert"."fizz",
        "buzz" = "upsert"."buzz"
when not matched then
    insert into "table1" (
        "foo", "bar", "fizz", "buzz"
    ) values (
        "upsert"."foo",
        "upsert"."bar",
        "upsert"."fizz",
        "upsert"."buzz"
    )
SQL
            ,
            self::createStandardSqlWriter()->format($query)
        );
    }

    public function testQueryOnConflictUpdateWithoutKey(): void
    {
        $query = (new MergeQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictUpdate()
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::assertSameSql(<<<SQL
merge into "table1"
using (
    select "a", "b", "c", "d" from "table2"
) as "upsert"
when matched then
    update set
        "foo" = "upsert"."foo",
        "bar" = "upsert"."bar",
        "fizz" = "upsert"."fizz",
        "buzz" = "upsert"."buzz"
when not matched then
    insert into "table1" (
        "foo", "bar", "fizz", "buzz"
    ) values (
        "upsert"."foo",
        "upsert"."bar",
        "upsert"."fizz",
        "upsert"."buzz"
    )
SQL
            ,
            self::createStandardSqlWriter()->format($query)
        );
    }
}
