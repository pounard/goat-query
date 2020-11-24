<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Driver\Query\WriterContext;
use Goat\Query\MergeQuery;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use PHPUnit\Framework\TestCase;

final class MySQL8MergeUnitTest extends TestCase
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

    public function testStringWithDotKeyRaiseError(): void
    {
        $query = new MergeQuery('table1');

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

    public function testValuesOnConflictIgnore(): void
    {
        $query = (new MergeQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictIgnore()
            ->values([1, 2, 3, 4])
            ->values([5, 6, 7, 8])
        ;

        self::assertSameSql(<<<SQL
insert ignore into "table1" (
    "foo", "bar", "fizz", "buzz"
)
values (
    ?, ?, ?, ?
), (
    ?, ?, ?, ?
)
SQL
            ,
            self::createMySQL8Writer()->format($query, new WriterContext())
        );
    }

    public function testValuesOnConflictIgnoreIgnoresKey(): void
    {
        $query = (new MergeQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictIgnore()
            ->setKey(['foo', 'bar'])
            ->values([1, 2, 3, 4])
            ->values([5, 6, 7, 8])
        ;

        self::assertSameSql(<<<SQL
insert ignore into "table1" (
    "foo", "bar", "fizz", "buzz"
)
values (
    ?, ?, ?, ?
), (
    ?, ?, ?, ?
)
SQL
            ,
            self::createMySQL8Writer()->format($query, new WriterContext())
        );
    }

    public function testValuesOnConflictUpdate(): void
    {
        $query = (new MergeQuery('table1'))
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
on duplicate key update
    "fizz" = new."fizz",
    "buzz" = new."buzz"
SQL
            ,
            self::createMySQL8Writer()->format($query, new WriterContext())
        );
    }

    public function testValuesValuesOnConflictUpdateWithoutKey(): void
    {
        $query = (new MergeQuery('table1'))
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
on duplicate key update
    "foo" = new."foo",
    "bar" = new."bar",
    "fizz" = new."fizz",
    "buzz" = new."buzz"
SQL
            ,
            self::createMySQL8Writer()->format($query, new WriterContext())
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
insert ignore into "table1" (
    "foo", "bar", "fizz", "buzz"
)
select "a", "b", "c", "d" from "table2"
SQL
            ,
            self::createMySQL8Writer()->format($query, new WriterContext())
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
insert ignore into "table1" (
    "foo", "bar", "fizz", "buzz"
)
select "a", "b", "c", "d" from "table2"
SQL
            ,
            self::createMySQL8Writer()->format($query, new WriterContext())
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
insert into "table1" (
    "foo", "bar", "fizz", "buzz"
)
select "a", "b", "c", "d" from "table2"
on duplicate key update
    "fizz" = new."fizz",
    "buzz" = new."buzz"
SQL
            ,
            self::createMySQL8Writer()->format($query, new WriterContext())
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
insert into "table1" (
    "foo", "bar", "fizz", "buzz"
)
select "a", "b", "c", "d" from "table2"
on duplicate key update
    "foo" = new."foo",
    "bar" = new."bar",
    "fizz" = new."fizz",
    "buzz" = new."buzz"
SQL
            ,
            self::createMySQL8Writer()->format($query, new WriterContext())
        );
    }
}
