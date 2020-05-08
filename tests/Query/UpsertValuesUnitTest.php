<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\QueryError;
use Goat\Query\UpsertValuesQuery;
use PHPUnit\Framework\TestCase;

final class UpsertValuesUnitTest extends TestCase
{
    use BuilderTestTrait;

    public function testEmptyValuesRaiseError(): void
    {
        $query = (new UpsertValuesQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
        ;

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/Cannot upsert default values/');

        self::createStandardFormatter()->format($query);
    }

    public function testStringWithDotKeyRaiseError(): void
    {
        $query = new UpsertValuesQuery('table1');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/column names in the primary/');

        $query->setKey(['foo.bar']);
    }

    public function testNonStringKeyRaiseError(): void
    {
        $query = new UpsertValuesQuery('table1');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/column names in the primary/');

        $query->setKey([new \DateTimeImmutable()]);
    }

    public function testInvalidConflictBehaviourRaiseError(): void
    {
        $query = new UpsertValuesQuery('table1');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/behaviours must be one/');

        $query->onConflict(7);
    }

    public function testOnConflictIgnore(): void
    {
        $query = (new UpsertValuesQuery('table1'))
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
            self::createStandardFormatter()->format($query)
        );
    }

    public function testOnConflictIgnoreIgnoresKey(): void
    {
        $query = (new UpsertValuesQuery('table1'))
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
            self::createStandardFormatter()->format($query)
        );
    }

    public function testOnConflictUpdate(): void
    {
        $query = (new UpsertValuesQuery('table1'))
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
            self::createStandardFormatter()->format($query)
        );
    }

    public function testOnConflictUpdateWithoutKey(): void
    {
        $query = (new UpsertValuesQuery('table1'))
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
            self::createStandardFormatter()->format($query)
        );
    }
}
