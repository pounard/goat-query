<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\ExpressionRaw;
use Goat\Query\ExpressionValue;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use Goat\Query\Where;
use PHPUnit\Framework\TestCase;

final class QuerySelectUnitTest extends TestCase
{
    use BuilderTestTrait;

    public function testEmptySelect()
    {
        $select = new SelectQuery('some_table');

        self::assertSameSql(
            'select * from "some_table"',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testForUpdate()
    {
        $select = new SelectQuery('some_table');
        self::assertFalse($select->isForUpdate());

        $select->forUpdate();
        self::assertTrue($select->isForUpdate());

        self::assertSameSql(
            'select * from "some_table" for update',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testPerformOnly()
    {
        $select = new SelectQuery('some_table');
        self::assertTrue($select->willReturnRows());

        $select->performOnly();
        self::assertFalse($select->willReturnRows());

        self::assertSameSql(
            'select * from "some_table"',
            self::createStandardFormatter()->format($select),
            "Will return row does not change formatting"
        );
    }

    public function testColumnWithName()
    {
        $select = new SelectQuery('some_table');

        $select->column('a');

        self::assertSameSql(
            'select "a" from "some_table"',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testColumnWithNameAndAlias()
    {
        $select = new SelectQuery('some_table');

        $select->column('a', 'my_alias');

        self::assertSameSql(
            'select "a" as "my_alias" from "some_table"',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testColumnWithRelationAndName()
    {
        $select = new SelectQuery('some_table');

        $select->column('foo.a');

        self::assertSameSql(
            'select "foo"."a" from "some_table"',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testColumnWithExpression()
    {
        $select = new SelectQuery('some_table');

        $select->column(ExpressionRaw::create('count(distinct foo)'), 'bar');

        self::assertSameSql(
            'select count(distinct foo) as "bar" from "some_table"',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testColumnExpressionWithColumnNameDoesNotEscape()
    {
        $select = new SelectQuery('some_table');
        $select->columnExpression('foo.a', 'my_alias');

        self::assertSameSql(
            'select foo.a as "my_alias" from "some_table"',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testColumnExpressionWithExpressionInstanceAndArgumentsRaiseException()
    {
        $select = new SelectQuery('some_table');

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/arguments/');

        $select->columnExpression(ExpressionRaw::create('count(*)'), null, 12);
    }

    public function testColumnExpressionWithNullRaiseError()
    {
        $select = new SelectQuery('some_table');

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/Expression cannot be null/');

        $select->columnExpression(null);
    }

    public function testColumnExpressionWithEmptyStringRaiseError()
    {
        $select = new SelectQuery('some_table');

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/Expression cannot be null/');

        $select->columnExpression('');
    }

    public function testColumnExpressionWithNonArrayArguments()
    {
        $select = new SelectQuery('some_table');

        $select->columnExpression('sum(?)', null, 12);

        self::assertSameSql(
            'select sum(?) from "some_table"',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testColumnWithCallbackWhichReturnsStringGetsEscaped()
    {
        $select = new SelectQuery('some_table');

        $select->column(
            function () {
                return "foo.bar";
            },
            'result'
        );

        self::assertSameSql(
            'select "foo"."bar" as "result" from "some_table"',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testColumnWithCallbackWhichReturnsExpression()
    {
        $select = new SelectQuery('some_table');

        $select->column(
            function () {
                return ExpressionRaw::create("foo.bar");
            },
            'result'
        );

        self::assertSameSql(
            'select foo.bar as "result" from "some_table"',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testColumnWithCallbackWhichReturnsStringIsNotEscaped()
    {
        $select = new SelectQuery('some_table');

        $select->columnExpression(
            function () {
                return "foo.bar";
            },
            'result'
        );

        self::assertSameSql(
            'select foo.bar as "result" from "some_table"',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testColumnExpressionWithCallback()
    {
        $select = new SelectQuery('some_table');

        $select->column(
            function () {
                return ExpressionRaw::create("count(*)");
            },
            'result'
        );

        self::assertSameSql(
            'select count(*) as "result" from "some_table"',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testColumns()
    {
        $select = new SelectQuery('some_table');

        $select->columns([
            // Just a column, no alias
            'foo.bar',

            // A colum, with an alias
            'id' => 'a.b',

            // An expresssion, no alias
            ExpressionRaw::create('count(a) as a'),

            // An expression, with alias
            'b' => ExpressionRaw::create('count(b)'),

            // A callback, no alias
            function () {
                return ExpressionRaw::create('count(c) as c');
            },

            // A callback, with alias
            'd' => function () {
                return ExpressionRaw::create('count(d)');
            },
        ]);

        self::assertSameSql('
            select
                "foo"."bar",
                "a"."b" as "id",
                count(a) as a,
                count(b) as "b",
                count(c) as c,
                count(d) as "d"
            from "some_table"',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testCondition()
    {
        $select = new SelectQuery('some_table');

        $select->condition('foo', 12);

        self::assertSameSql(
            'select * from "some_table" where "foo" = ?',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testConditionWithExpression()
    {
        $select = new SelectQuery('some_table');

        $select->condition('bar', ExpressionRaw::create('12'));

        self::assertSameSql(
            'select * from "some_table" where "bar" = 12',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testConditionWithExpressionValueWillCast()
    {
        $select = new SelectQuery('some_table');

        $select->condition('baz', ExpressionValue::create(12, 'json'));

        self::assertSameSql(
            'select * from "some_table" where "baz" = ?::json',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testConditionWithCallbackCastAsArgument()
    {
        $select = new SelectQuery('some_table');

        $select->condition('boo', function () {
            return '12';
        });

        self::assertSameSql(
            'select * from "some_table" where "boo" = ?',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testConditionWithCallbackReturningExpression()
    {
        $select = new SelectQuery('some_table');

        $select->condition('baa', function () {
            return ExpressionRaw::create('now()');
        }, '<');

        self::assertSameSql(
            'select * from "some_table" where "baa" < now()',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testConditionWithCallbackReturningNothing()
    {
        $select = new SelectQuery('some_table');

        $select->condition(function (\Goat\Query\Where $where) {
            $where
                ->isEqual('id', 12)
                ->isGreaterOrEqual('birthdate', new \DateTimeImmutable('2019-09-24'))
            ;
        });

        self::assertSameSql(
            'select * from "some_table" where "id" = ? and "birthdate" >= ?',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testExpression()
    {
        $select = new SelectQuery('some_table');

        $select->expression('a < b');

        self::assertSameSql(
            'select * from "some_table" where a < b',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testExpressionWithCallback()
    {
        $select = new SelectQuery('some_table');

        $select->expression(function (Where $where) {
            $where->condition('a', 56);
        });

        self::assertSameSql(
            'select * from "some_table" where "a" = ?',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testExpressionWithNullRaiseError()
    {
        $select = new SelectQuery('some_table');

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/Expression cannot be null/');

        $select->expression(null);
    }

    public function testExpressionWithEmptyStringRaiseError()
    {
        $select = new SelectQuery('some_table');

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/Expression cannot be null/');

        $select->expression('');
    }

    public function testHaving()
    {
        $select = new SelectQuery('some_table');

        $select->having('foo', 12);

        self::assertSameSql(
            'select * from "some_table" having "foo" = ?',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testHavingWithExpression()
    {
        $select = new SelectQuery('some_table');

        $select->having('bar', ExpressionRaw::create('12'));

        self::assertSameSql(
            'select * from "some_table" having "bar" = 12',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testHavingWithExpressionValueWillCast()
    {
        $select = new SelectQuery('some_table');

        $select->having('baz', ExpressionValue::create(12, 'json'));

        self::assertSameSql(
            'select * from "some_table" having "baz" = ?::json',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testHavingWithCallbackCastAsArgument()
    {
        $select = new SelectQuery('some_table');

        $select->having('boo', function () {
            return '12';
        });

        self::assertSameSql(
            'select * from "some_table" having "boo" = ?',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testHavingWithCallbackReturningExpression()
    {
        $select = new SelectQuery('some_table');

        $select->having('baa', function () {
            return ExpressionRaw::create('now()');
        }, '<');

        self::assertSameSql(
            'select * from "some_table" having "baa" < now()',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testHavingExpression()
    {
        $select = new SelectQuery('some_table');

        $select->havingExpression('a < b');

        self::assertSameSql(
            'select * from "some_table" having a < b',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testHavingExpressionWithCallback()
    {
        $select = new SelectQuery('some_table');

        $select->havingExpression(function (Where $where) {
            $where->condition('a', 56);
        });

        self::assertSameSql(
            'select * from "some_table" having "a" = ?',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testRange()
    {
        $select = new SelectQuery('some_table');

        $select->range(10, 3);

        self::assertSameSql(
            'select * from "some_table" limit 10 offset 3',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testRangeWithNegativeOffsetRaiseError()
    {
        $select = new SelectQuery('some_table');

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/offset must be a positive integer/');

        $select->range(10, -1);
    }

    public function testRangeWithNegativeLimitRaiseError()
    {
        $select = new SelectQuery('some_table');

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/limit must be a positive integer/');

        $select->range(-1, 10);
    }

    public function testPage()
    {
        $select = new SelectQuery('some_table');

        $select->page(10, 3);

        self::assertSameSql(
            'select * from "some_table" limit 10 offset 20',
            self::createStandardFormatter()->format($select)
        );
    }

    public function testPageWith0RaiseError()
    {
        $select = new SelectQuery('some_table');

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/page must be a positive integer/');

        $select->page(10, 0);
    }
}