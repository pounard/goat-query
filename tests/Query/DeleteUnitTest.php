<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\DeleteQuery;
use Goat\Query\ExpressionConstantTable;
use Goat\Query\ExpressionRaw;
use PHPUnit\Framework\TestCase;

/**
 * It does not test all possibles variations of:
 *
 *  - condition()
 *  - expression()
 * 
 * Since the are done with SelectUnitTest, which in the end goes to Where.
 */
final class DeleteUnitTest extends TestCase
{
    use BuilderTestTrait;

    public function testCoverageForClone()
    {
        $select = new DeleteQuery('d');
        $select
            ->with('sdf', ExpressionConstantTable::create()->row([1, 2]))
            ->from('a')
            ->from('b')
            ->join('c')
            ->where('foo', 42)
            ->returning('*')
        ;

        $cloned = clone $select;

        self::assertSameSql(
            self::createStandardSqlWriter()->format($select),
            self::createStandardSqlWriter()->format($cloned)
        );
    }

    public function testEmptyDelete()
    {
        $delete = new DeleteQuery('some_table');

        self::assertSameSql(
            'delete from "some_table"',
            self::createStandardSqlWriter()->format($delete)
        );
    }

    public function testCondition()
    {
        $delete = new DeleteQuery('some_table');

        $delete->where('a', 12);

        self::assertSameSql(
            'delete from "some_table" where "a" = ?',
            self::createStandardSqlWriter()->format($delete)
        );
    }

    public function testExpression()
    {
        $delete = new DeleteQuery('some_table');

        $delete->whereExpression('true');

        self::assertSameSql(
            'delete from "some_table" where true',
            self::createStandardSqlWriter()->format($delete)
        );
    }

    public function testReturningEverything()
    {
        $delete = new DeleteQuery('some_table');

        $delete->returning();

        self::assertSameSql(
            'delete from "some_table" returning *',
            self::createStandardSqlWriter()->format($delete)
        );
    }

    public function testReturningColumn()
    {
        $delete = new DeleteQuery('some_table');

        $delete->returning('a');

        self::assertSameSql(
            'delete from "some_table" returning "a"',
            self::createStandardSqlWriter()->format($delete)
        );
    }

    public function testRetuningColumnWithAlias()
    {
        $delete = new DeleteQuery('some_table');

        $delete->returning('foo.a');

        self::assertSameSql(
            'delete from "some_table" returning "foo"."a"',
            self::createStandardSqlWriter()->format($delete)
        );
    }

    public function testReturningExpression()
    {
        $delete = new DeleteQuery('some_table');

        $delete->returning(ExpressionRaw::create('a + 2'), 'a_plus_two');

        self::assertSameSql(
            'delete from "some_table" returning a + 2 as "a_plus_two"',
            self::createStandardSqlWriter()->format($delete)
        );
    }
}
