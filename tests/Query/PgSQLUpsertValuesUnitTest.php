<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\QueryError;
use Goat\Query\UpsertValuesQuery;
use PHPUnit\Framework\TestCase;

final class PgSQLUpsertValuesUnitTest extends TestCase
{
    use BuilderTestTrait;

    public function testEmptyValuesRaiseError(): void
    {
        $query = (new UpsertValuesQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
        ;

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/Cannot upsert default values/');

        self::createPgSQLWriter()->format($query);
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
insert into "table1" (
    "foo", "bar", "fizz", "buzz"
)
values (
    ?, ?, ?, ?
), (
    ?, ?, ?, ?
)
on conflict do nothing
SQL
            ,
            self::createPgSQLWriter()->format($query)
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
insert into "table1" (
    "foo", "bar", "fizz", "buzz"
)
values (
    ?, ?, ?, ?
), (
    ?, ?, ?, ?
)
on conflict do nothing
SQL
            ,
            self::createPgSQLWriter()->format($query)
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
insert into "table1" (
    "foo", "bar", "fizz", "buzz"
)
values (
    ?, ?, ?, ?
), (
    ?, ?, ?, ?
)
on conflict do update set
    "fizz" = excluded."fizz",
    "buzz" = excluded."buzz"
SQL
            ,
            self::createPgSQLWriter()->format($query)
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
insert into "table1" (
    "foo", "bar", "fizz", "buzz"
)
values (
    ?, ?, ?, ?
), (
    ?, ?, ?, ?
)
on conflict do update set
    "foo" = excluded."foo",
    "bar" = excluded."bar",
    "fizz" = excluded."fizz",
    "buzz" = excluded."buzz"
SQL
            ,
            self::createPgSQLWriter()->format($query)
        );
    }
}
