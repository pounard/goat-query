<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use Goat\Query\UpsertQueryQuery;
use PHPUnit\Framework\TestCase;

final class UpsertQueryUnitTest extends TestCase
{
    use BuilderTestTrait;

    public function testStringWithDotKeyRaiseError(): void
    {
        $query = new UpsertQueryQuery('table1');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/column names in the primary/');

        $query->setKey(['foo.bar']);
    }

    public function testNonStringKeyRaiseError(): void
    {
        $query = new UpsertQueryQuery('table1');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/column names in the primary/');

        $query->setKey([new \DateTimeImmutable()]);
    }

    public function testInvalidConflictBehaviourRaiseError(): void
    {
        $query = new UpsertQueryQuery('table1');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/behaviours must be one/');

        $query->onConflict(7);
    }

    private function createUsingQuery(): Query
    {
        return (new SelectQuery('table2'))
            ->column('a')
            ->column('b')
            ->column('c')
            ->column('d')
        ;
    }

    public function testOnConflictIgnore(): void
    {
        $query = (new UpsertQueryQuery('table1'))
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
            self::createStandardFormatter()->format($query)
        );
    }

    public function testOnConflictIgnoreIgnoresKey(): void
    {
        $query = (new UpsertQueryQuery('table1'))
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
            self::createStandardFormatter()->format($query)
        );
    }

    public function testOnConflictUpdate(): void
    {
        $query = (new UpsertQueryQuery('table1'))
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
            self::createStandardFormatter()->format($query)
        );
    }

    public function testOnConflictUpdateWithoutKey(): void
    {
        $query = (new UpsertQueryQuery('table1'))
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
            self::createStandardFormatter()->format($query)
        );
    }
}
