<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use Goat\Query\UpsertQueryQuery;
use PHPUnit\Framework\TestCase;

final class PgSQLUpsertQueryUnitTest extends TestCase
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
insert into "table1" (
    "foo", "bar", "fizz", "buzz"
)
select "a", "b", "c", "d" from "table2"
on conflict do nothing
SQL
            ,
            self::createPgSQLWriter()->format($query)
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
insert into "table1" (
    "foo", "bar", "fizz", "buzz"
)
select "a", "b", "c", "d" from "table2"
on conflict do nothing
SQL
            ,
            self::createPgSQLWriter()->format($query)
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
insert into "table1" (
    "foo", "bar", "fizz", "buzz"
)
select "a", "b", "c", "d" from "table2"
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
        $query = (new UpsertQueryQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictUpdate()
            ->query(
                $this->createUsingQuery()
            )
        ;

        self::assertSameSql(<<<SQL
insert into "table1" (
    "foo", "bar", "fizz", "buzz"
)
select "a", "b", "c", "d" from "table2"
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
