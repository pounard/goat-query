<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\ExpressionConstantTable;
use Goat\Query\ExpressionRaw;
use Goat\Query\ExpressionValue;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use Goat\Query\Where;
use PHPUnit\Framework\TestCase;

final class QuerySelectUnitTest extends TestCase
{
    use BuilderTestTrait;

    public function testCoverageForClone(): void
    {
        $select = new SelectQuery();
        $select
            ->with('sdf', ExpressionConstantTable::create()->row([1, 2]))
            ->from('a')
            ->from('b')
            ->join('c')
            ->column('bar')
            ->orderBy('baz')
            ->having('fizz', 'buzz')
            ->where('foo', 42)
        ;

        $cloned = clone $select;

        self::assertSameSql(
            self::format($select),
            self::format($cloned)
        );
    }

    public function testEmptySelect(): void
    {
        $select = new SelectQuery('some_table');

        self::assertSameSql(
            'select * from "some_table"',
            self::format($select)
        );
    }

    public function testForUpdate(): void
    {
        $select = new SelectQuery('some_table');
        self::assertFalse($select->isForUpdate());

        $select->forUpdate();
        self::assertTrue($select->isForUpdate());

        self::assertSameSql(
            'select * from "some_table" for update',
            self::format($select)
        );
    }

    public function testPerformOnly(): void
    {
        $select = new SelectQuery('some_table');
        self::assertTrue($select->willReturnRows());

        $select->performOnly();
        self::assertFalse($select->willReturnRows());

        self::assertSameSql(
            'select * from "some_table"',
            self::format($select),
            "Will return row does not change formatting"
        );
    }

    public function testColumnWithName(): void
    {
        $select = new SelectQuery('some_table');

        $select->column('a');

        self::assertSameSql(
            'select "a" from "some_table"',
            self::format($select)
        );
    }

    public function testColumnWithNameAndAlias(): void
    {
        $select = new SelectQuery('some_table');

        $select->column('a', 'my_alias');

        self::assertSameSql(
            'select "a" as "my_alias" from "some_table"',
            self::format($select)
        );
    }

    public function testColumnWithTableAndName(): void
    {
        $select = new SelectQuery('some_table');

        $select->column('foo.a');

        self::assertSameSql(
            'select "foo"."a" from "some_table"',
            self::format($select)
        );
    }

    public function testColumnWithExpression(): void
    {
        $select = new SelectQuery('some_table');

        $select->column(ExpressionRaw::create('count(distinct foo)'), 'bar');

        self::assertSameSql(
            'select count(distinct foo) as "bar" from "some_table"',
            self::format($select)
        );
    }

    public function testColumnExpressionWithColumnNameDoesNotEscape(): void
    {
        $select = new SelectQuery('some_table');
        $select->columnExpression('foo.a', 'my_alias');

        self::assertSameSql(
            'select foo.a as "my_alias" from "some_table"',
            self::format($select)
        );
    }

    public function testColumnExpressionWithExpressionInstanceAndArgumentsRaiseException(): void
    {
        $select = new SelectQuery('some_table');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/arguments/');

        $select->columnExpression(ExpressionRaw::create('count(*)'), null, 12);
    }

    public function testColumnExpressionWithNullRaiseError(): void
    {
        $select = new SelectQuery('some_table');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/Expression cannot be null/');

        $select->columnExpression(null);
    }

    public function testColumnExpressionWithEmptyStringRaiseError(): void
    {
        $select = new SelectQuery('some_table');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/Expression cannot be null/');

        $select->columnExpression('');
    }

    public function testColumnExpressionWithNonArrayArguments(): void
    {
        $select = new SelectQuery('some_table');

        $select->columnExpression('sum(?)', null, 12);

        self::assertSameSql(
            'select sum(?) from "some_table"',
            self::format($select)
        );
    }

    public function testColumnWithCallbackWhichReturnsStringGetsEscaped(): void
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
            self::format($select)
        );
    }

    public function testColumnWithCallbackWhichReturnsExpression(): void
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
            self::format($select)
        );
    }

    public function testColumnWithCallbackWhichReturnsStringIsNotEscaped(): void
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
            self::format($select)
        );
    }

    public function testColumnExpressionWithCallback(): void
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
            self::format($select)
        );
    }

    public function testColumns(): void
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

            // A short arrow function, no alias
            fn () => ExpressionRaw::create('count(e) as e'),

            // A short arrow function, with alias
            'f' => fn () => ExpressionRaw::create('count(f)'),
        ]);

        self::assertSameSql('
            select
                "foo"."bar",
                "a"."b" as "id",
                count(a) as a,
                count(b) as "b",
                count(c) as c,
                count(d) as "d",
                count(e) as e,
                count(f) as "f"
            from "some_table"',
            self::format($select)
        );
    }

    public function testCondition(): void
    {
        $select = new SelectQuery('some_table');

        $select->where('foo', 12);

        self::assertSameSql(
            'select * from "some_table" where "foo" = ?',
            self::format($select)
        );
    }

    public function testConditionWithExpression(): void
    {
        $select = new SelectQuery('some_table');

        $select->where('bar', ExpressionRaw::create('12'));

        self::assertSameSql(
            'select * from "some_table" where "bar" = 12',
            self::format($select)
        );
    }

    public function testConditionWithExpressionValueWillCast(): void
    {
        $select = new SelectQuery('some_table');

        $select->where('baz', ExpressionValue::create(12, 'json'));

        self::assertSameSql(
            'select * from "some_table" where "baz" = ?::json',
            self::format($select)
        );
    }

    public function testConditionWithCallbackCastAsArgument(): void
    {
        $select = new SelectQuery('some_table');

        $select->where('boo', function () {
            return '12';
        });

        self::assertSameSql(
            'select * from "some_table" where "boo" = ?',
            self::format($select)
        );
    }

    public function testConditionWithWhereInstance(): void
    {
        $select = new SelectQuery('some_table');

        $select->where((new Where(Where::OR))
            ->isNull('foo')
            ->isNull('bar')
        );

        self::assertSameSql(
            'select * from "some_table" where ("foo" is null or "bar" is null)',
            self::format($select)
        );
    }

    public function testConditionWithWhereInstanceAndValueFails(): void
    {
        $select = new SelectQuery('some_table');

        self::expectException(QueryError::class);
        $select->where(new Where(Where::OR), 'foo');
    }

    public function testExpressionWithWhereInstance()
    {
        $select = new SelectQuery('some_table');

        $select->whereExpression((new Where(Where::OR))
            ->isNull('foo')
            ->isNull('bar')
        );

        self::assertSameSql(
            'select * from "some_table" where ("foo" is null or "bar" is null)',
            self::format($select)
        );
    }

    public function testConditionWithCallbackReturningExpression(): void
    {
        $select = new SelectQuery('some_table');

        $select->where('baa', function () {
            return ExpressionRaw::create('now()');
        }, '<');

        self::assertSameSql(
            'select * from "some_table" where "baa" < now()',
            self::format($select)
        );
    }

    public function testConditionWithCallbackReturningNothing(): void
    {
        $select = new SelectQuery('some_table');

        $select->where(function (Where $where) {
            $where
                ->isEqual('id', 12)
                ->isGreaterOrEqual('birthdate', new \DateTimeImmutable('2019-09-24'))
            ;
        });

        self::assertSameSql(
            'select * from "some_table" where "id" = ? and "birthdate" >= ?',
            self::format($select)
        );
    }

    public function testConditionWithShortArrowFunction(): void
    {
        $select = new SelectQuery('some_table');

        $select->where(fn (Where $where) => $where->isLess('foo', 'bar'));

        self::assertSameSql(
            'select * from "some_table" where "foo" < ?',
            self::format($select)
        );
    }

    public function testExpression(): void
    {
        $select = new SelectQuery('some_table');

        $select->whereExpression('a < b');

        self::assertSameSql(
            'select * from "some_table" where a < b',
            self::format($select)
        );
    }

    public function testExpressionWithCallback(): void
    {
        $select = new SelectQuery('some_table');

        $select->whereExpression(function (Where $where) {
            $where->condition('a', 56);
        });

        self::assertSameSql(
            'select * from "some_table" where "a" = ?',
            self::format($select)
        );
    }

    public function testExpressionWithShortArrowFunction(): void
    {
        $select = new SelectQuery('some_table');

        $select->whereExpression(fn (Where $where) => $where->isLess('foo', 'bar'));

        self::assertSameSql(
            'select * from "some_table" where "foo" < ?',
            self::format($select)
        );
    }

    public function testExpressionWithNullRaiseError(): void
    {
        $select = new SelectQuery('some_table');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/Expression cannot be null/');

        $select->whereExpression(null);
    }

    public function testExpressionWithEmptyStringRaiseError(): void
    {
        $select = new SelectQuery('some_table');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/Expression cannot be null/');

        $select->whereExpression('');
    }

    public function testHaving(): void
    {
        $select = new SelectQuery('some_table');

        $select->having('foo', 12);

        self::assertSameSql(
            'select * from "some_table" having "foo" = ?',
            self::format($select)
        );
    }

    public function testHavingWithExpression(): void
    {
        $select = new SelectQuery('some_table');

        $select->having('bar', ExpressionRaw::create('12'));

        self::assertSameSql(
            'select * from "some_table" having "bar" = 12',
            self::format($select)
        );
    }

    public function testHavingWithExpressionValueWillCast(): void
    {
        $select = new SelectQuery('some_table');

        $select->having('baz', ExpressionValue::create(12, 'json'));

        self::assertSameSql(
            'select * from "some_table" having "baz" = ?::json',
            self::format($select)
        );
    }

    public function testHavingWithCallbackCastAsArgument(): void
    {
        $select = new SelectQuery('some_table');

        $select->having('boo', function () {
            return '12';
        });

        self::assertSameSql(
            'select * from "some_table" having "boo" = ?',
            self::format($select)
        );
    }

    public function testHavingWithCallbackReturningExpression(): void
    {
        $select = new SelectQuery('some_table');

        $select->having('baa', function () {
            return ExpressionRaw::create('now()');
        }, '<');

        self::assertSameSql(
            'select * from "some_table" having "baa" < now()',
            self::format($select)
        );
    }

    public function testHavingExpression(): void
    {
        $select = new SelectQuery('some_table');

        $select->havingExpression('a < b');

        self::assertSameSql(
            'select * from "some_table" having a < b',
            self::format($select)
        );
    }

    public function testHavingExpressionWithCallback(): void
    {
        $select = new SelectQuery('some_table');

        $select->havingExpression(function (Where $where) {
            $where->condition('a', 56);
        });

        self::assertSameSql(
            'select * from "some_table" having "a" = ?',
            self::format($select)
        );
    }

    public function testExpressionAsJoin(): void
    {
        $expression = ExpressionConstantTable::create();
        $expression->row([1, 2, 3]);

        $select = new SelectQuery('foo');
        $select->join($expression);

        self::assertSameSql(
            <<<SQL
            select * from "foo"
            inner join (
                values (?, ?, ?)
            ) as "goat_1"
            SQL,
            self::format($select)
        );
    }

    public function testExpressionAsJoinWithAlias(): void
    {
        $expression = ExpressionConstantTable::create();
        $expression->row([1, 2, 3]);

        $select = new SelectQuery('foo');
        $select->join($expression, null, 'mooh');

        self::assertSameSql(
            <<<SQL
            select * from "foo"
            inner join (
                values (?, ?, ?)
            ) as "mooh"
            SQL,
            self::format($select)
        );
    }

    /*
     * @todo Fix formatting bug with AliasedExpression
     *
    public function testExpressionAsJoinWithAliasInExpression()
    {
        $expression = ExpressionConstantTable::create();
        $expression->row([1, 2, 3]);

        $select = new SelectQuery(new AliasedExpression('f.u.b.a.r.', $expression));

        self::assertSameSql(
            <<<SQL
            select *
            from (
                values
                (?, ?, ?)
            ) as "f.u.b.a.r."
            SQL,
            self::format($select)
        );
    }
     */

    public function testExpressionAsTable(): void
    {
        $expression = ExpressionConstantTable::create();
        $expression->row([1, 2, 3]);
        $expression->row(["a", "b", "c"]);

        $select = new SelectQuery($expression);

        self::assertSameSql(
            <<<SQL
            select *
            from (
                values
                (?, ?, ?),
                (?, ?, ?)
            ) as "goat_1"
            SQL,
            self::format($select)
        );
    }

    public function testExpressionAsTableWithAlias(): void
    {
        $expression = ExpressionConstantTable::create();
        $expression->row([1, 2, 3]);

        $select = new SelectQuery($expression, 'foobar');

        self::assertSameSql(
            <<<SQL
            select *
            from (
                values
                (?, ?, ?)
            ) as "foobar"
            SQL,
            self::format($select)
        );
    }

    /*
     * @todo Fix formatting bug with AliasedExpression
     *
    public function testExpressionAsTableWithAliasInExpression()
    {
        $expression = ExpressionConstantTable::create();
        $expression->row([1, 2, 3]);

        $select = new SelectQuery(new AliasedExpression('f.u.b.a.r.', $expression));

        self::assertSameSql(
            <<<SQL
            select *
            from (
                values
                (?, ?, ?)
            ) as "f.u.b.a.r."
            SQL,
            self::format($select)
        );
    }
     */

    public function testRange(): void
    {
        $select = new SelectQuery('some_table');

        $select->range(10, 3);

        self::assertSameSql(
            'select * from "some_table" limit 10 offset 3',
            self::format($select)
        );
    }

    public function testRangeWithNegativeOffsetRaiseError(): void
    {
        $select = new SelectQuery('some_table');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/offset must be a positive integer/');

        $select->range(10, -1);
    }

    public function testRangeWithNegativeLimitRaiseError(): void
    {
        $select = new SelectQuery('some_table');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/limit must be a positive integer/');

        $select->range(-1, 10);
    }

    public function testPage(): void
    {
        $select = new SelectQuery('some_table');

        $select->page(10, 3);

        self::assertSameSql(
            'select * from "some_table" limit 10 offset 20',
            self::format($select)
        );
    }

    public function testPageWith0RaiseError(): void
    {
        $select = new SelectQuery('some_table');

        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/page must be a positive integer/');

        $select->page(10, 0);
    }
}
