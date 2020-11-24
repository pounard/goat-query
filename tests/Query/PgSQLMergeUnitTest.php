<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\MergeQuery;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use PHPUnit\Framework\TestCase;

final class PgSQLMergeUnitTest extends TestCase
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
        $query = (new MergeQuery('table1'))
            ->columns(['foo', 'bar', 'fizz', 'buzz'])
            ->onConflictUpdate()
            ->values([1, 2, 3, 4])
        ;

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/Key must be specified/');

        self::createPgSQLWriter()->prepare($query);
    }

    public function testKeyIsMandatoryWithOnConflictUpdate(): void
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
            self::createPgSQLWriter()->prepare($query)
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
            self::createPgSQLWriter()->prepare($query)
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
on conflict ("foo", "bar")
    do update set
        "fizz" = excluded."fizz",
        "buzz" = excluded."buzz"
SQL
            ,
            self::createPgSQLWriter()->prepare($query)
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
insert into "table1" (
    "foo", "bar", "fizz", "buzz"
)
select "a", "b", "c", "d" from "table2"
on conflict do nothing
SQL
            ,
            self::createPgSQLWriter()->prepare($query)
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
insert into "table1" (
    "foo", "bar", "fizz", "buzz"
)
select "a", "b", "c", "d" from "table2"
on conflict do nothing
SQL
            ,
            self::createPgSQLWriter()->prepare($query)
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
on conflict ("foo", "bar")
    do update set
        "fizz" = excluded."fizz",
        "buzz" = excluded."buzz"
SQL
            ,
            self::createPgSQLWriter()->prepare($query)
        );
    }
}
