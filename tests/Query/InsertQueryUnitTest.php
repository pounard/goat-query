<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\InsertQuery;
use Goat\Query\Query;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use PHPUnit\Framework\TestCase;

final class InsertQueryUnitTest extends TestCase
{
    use BuilderTestTrait;

    private function createConstantTable(): Query
    {
        $select = new SelectQuery('other_table');
        $select->columns(['foo', 'bar']);
        $select->where('baz', true);

        return $select;
    }

    private function createSelectQuery(): Query
    {
        return (new SelectQuery('other_table'))
            ->columns(['foo', 'bar'])
            ->where('baz', true)
        ;
    }

    public function testRaiseErrorIfValuesIsCalledAfterQuery(): void
    {
        $insert = (new InsertQuery('some_table'))
            ->columns(['pif', 'paf'])
            ->query(
                $this->createSelectQuery()
            )
        ;

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/mutually exclusive/');

        $insert->values(['foo', 'bar']);
    }

    public function testRaiseErrorIfQueryIsCalledAfterValues(): void
    {
        $insert = (new InsertQuery('some_table'))
            ->columns(['pif', 'paf'])
            ->values(['foo', 'bar'])
        ;

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/was already set/');

        $insert->query(
            $this->createSelectQuery()
        );
    }

    public function testQueryInsertBasics(): void
    {
        $insert = (new InsertQuery('some_table'))
            ->columns(['pif', 'paf'])
            ->query(
                $this->createSelectQuery()
            )
        ;

        self::assertSameSql(
            'insert into "some_table" ("pif", "paf") select "foo", "bar" from "other_table" where "baz" = ?',
            self::format($insert)
        );
    }

    public function testInsertQueryWithoutQueryFails(): void
    {
        $insert = new InsertQuery('some_table');
        $insert->columns(['pif', 'paf']);

        self::expectException(QueryError::class);
        self::format($insert);
    }

    public function testInsertValuesUsesColumnsFromFirst(): void
    {
        $insert = new InsertQuery('some_table');
        $insert->values([
            'foo' => 'bar',
            'int' => 3,
        ]);

        self::assertSameSql(
            'insert into "some_table" ("foo", "int") values (?, ?)',
            self::format($insert)
        );
    }

    public function testInsertValuesIngoreKeysFromNext(): void
    {
        $insert = new InsertQuery('some_table');
        $insert->values([
            'foo' => 'bar',
            'int' => 3,
        ]);
        $insert->values([
            'pif' => 'pouf',
            'paf' => 3,
        ]);

        self::assertSameSql(
            'insert into "some_table" ("foo", "int") values (?, ?), (?, ?)',
            self::format($insert)
        );
    }

    public function testInsertValuesWithColumnCall(): void
    {
        $insert = new InsertQuery('some_table');
        $insert->columns(['a', 'b']);
        $insert->values([
            'foo' => 'bar',
            'int' => 3,
        ]);
        $insert->values([
            'pouf',
            3,
        ]);

        self::assertSameSql(
            'insert into "some_table" ("a", "b") values (?, ?), (?, ?)',
            self::format($insert)
        );
    }
}
