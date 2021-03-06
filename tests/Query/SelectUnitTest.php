<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\QueryError;
use Goat\Query\RawQuery;
use Goat\Query\SelectQuery;
use Goat\Query\Where;
use Goat\Query\Expression\CastExpression;
use Goat\Query\Expression\ConstantRowExpression;
use Goat\Query\Expression\ConstantTableExpression;
use Goat\Query\Expression\RawExpression;
use Goat\Query\Expression\ValueExpression;
use PHPUnit\Framework\TestCase;

final class SelectUnitTest extends TestCase
{
    use BuilderTestTrait;

    public function testCoverageForClone(): void
    {
        $select = new SelectQuery();
        $select
            ->with('sdf', new ConstantTableExpression([[1, 2]]))
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

    public function testWhereAnd(): void
    {
        $select = (new SelectQuery('a'))
            ->whereAnd(fn (Where $where) => $where
                ->condition('c', 13)
                ->condition('d', 14)
            )
            ->where('b', 12)
        ;

        self::assertSameSql(
            'select * from "a" where ("c" = ? and "d" = ?) and "b" = ?',
            self::format($select)
        );
    }

    public function testWhereOr(): void
    {
        $select = (new SelectQuery('a'))
            ->whereOr(fn (Where $where) => $where
                ->condition('c', 13)
                ->condition('d', 14)
            )
            ->where('b', 12)
        ;

        self::assertSameSql(
            'select * from "a" where ("c" = ? or "d" = ?) and "b" = ?',
            self::format($select)
        );
    }

    public function testHavingAnd(): void
    {
        $select = (new SelectQuery('a'))
            ->havingAnd(fn (Where $where) => $where
                ->condition('c', 13)
                ->condition('d', 14)
            )
            ->having('b', 12)
        ;

        self::assertSameSql(
            'select * from "a" having ("c" = ? and "d" = ?) and "b" = ?',
            self::format($select)
        );
    }

    public function testHavingOr(): void
    {
        $select = (new SelectQuery('a'))
            ->havingOr(fn (Where $where) => $where
                ->condition('c', 13)
                ->condition('d', 14)
            )
            ->having('b', 12)
        ;

        self::assertSameSql(
            'select * from "a" having ("c" = ? or "d" = ?) and "b" = ?',
            self::format($select)
        );
    }

    public function testHavingWithOrderAndLimit(): void
    {
        $select = (new SelectQuery('a'))
            ->having('b', 12)
            ->orderBy('foo')
            ->groupBy('bar')
            ->range(12, 7)
        ;

        self::assertSameSql(
            'select * from "a" group by "bar" having "b" = ? order by "foo" asc limit 12 offset 7',
            self::format($select)
        );
    }

    public function testUnion(): void
    {
        $select = new SelectQuery('some_table');

        $select->createUnion('other_table');
        $select->createUnion('again_and_again');

        self::assertSameSql(
            <<<SQL
            select * from "some_table"
            union
            select * from "other_table"
            union
            select * from "again_and_again"
            SQL,
            self::format($select)
        );
    }

    public function testUnionRecursive(): void
    {
        $select = new SelectQuery('some_table');

        $other = $select->createUnion('other_table');

        $other->createUnion('another_one');

        self::assertSameSql(
            <<<SQL
            select * from "some_table"
            union
            select * from "other_table"
            union
            select * from "another_one"
            SQL,
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

        $select->column(new RawExpression('count(distinct foo)'), 'bar');

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

        $select->columnExpression(new RawExpression('count(*)'), null, 12);
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
                return new RawExpression("foo.bar");
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
                return new RawExpression("count(*)");
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
            new RawExpression('count(a) as a'),

            // An expression, with alias
            'b' => new RawExpression('count(b)'),

            // A callback, no alias
            function () {
                return new RawExpression('count(c) as c');
            },

            // A callback, with alias
            'd' => function () {
                return new RawExpression('count(d)');
            },

            // A short arrow function, no alias
            fn () => new RawExpression('count(e) as e'),

            // A short arrow function, with alias
            'f' => fn () => new RawExpression('count(f)'),
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

        $select->where('bar', new RawExpression('12'));

        self::assertSameSql(
            'select * from "some_table" where "bar" = 12',
            self::format($select)
        );
    }

    public function testConditionWithValueExpressionWillCast(): void
    {
        $select = new SelectQuery('some_table');

        $select->where('baz', new ValueExpression(12, 'json'));

        $formatted = self::prepare($select);

        self::assertSameSql(
            'select * from "some_table" where "baz" = ?',
            $formatted
        );

        self::assertSame('json', $formatted->getArgumentTypes()[0]);
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
            return new RawExpression('now()');
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

        $select->having('bar', new RawExpression('12'));

        self::assertSameSql(
            'select * from "some_table" having "bar" = 12',
            self::format($select)
        );
    }

    public function testHavingWithValueExpressionWillCast(): void
    {
        $select = new SelectQuery('some_table');

        $select->having('baz', new ValueExpression(12, 'json'));

        $formatted = self::prepare($select);

        self::assertSameSql(
            'select * from "some_table" having "baz" = ?',
            $formatted
        );

        self::assertSame('json', $formatted->getArgumentTypes()[0]);
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
            return new RawExpression('now()');
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
        $expression = new ConstantTableExpression();
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
        $expression = new ConstantTableExpression();
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
        $expression = new ConstantTableExpression();
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
        $expression = new ConstantTableExpression();
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
        $expression = new ConstantTableExpression();
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

    public function testExpressionAsTableWithAliasAndColumnAliases(): void
    {
        $expression = new ConstantTableExpression();
        $expression->columns(['foo', 'bar', 'baz']);
        $expression->row([1, 2, 3]);

        $select = new SelectQuery($expression, 'foobar');

        self::assertSameSql(
            <<<SQL
            select *
            from (
                values
                (?, ?, ?)
            ) as "foobar" ("foo", "bar", "baz")
            SQL,
            self::format($select)
        );
    }

    public function testExpressionInCteWithAlias(): void
    {
        $expression = new ConstantTableExpression();
        $expression->row([1, 2, 3]);

        $select = new SelectQuery('temp_table');
        $select->with('temp_table', $expression);

        self::assertSameSql(
            <<<SQL
            with "temp_table" as (values (?, ?, ?))
            select *
            from "temp_table"
            SQL,
            self::format($select)
        );
    }

    public function testExpressionInCteWithAliasAndColumnAliases(): void
    {
        $expression = new ConstantTableExpression();
        $expression->columns(['foo', 'bar', 'baz']);
        $expression->row([1, 2, 3]);

        $select = new SelectQuery('temp_table');
        $select->with('temp_table', $expression);

        self::assertSameSql(
            <<<SQL
            with "temp_table" ("foo", "bar", "baz") as (values (?, ?, ?))
            select *
            from "temp_table"
            SQL,
            self::format($select)
        );
    }

    /*
     * @todo Fix formatting bug with AliasedExpression
     *
    public function testExpressionAsTableWithAliasInExpression()
    {
        $expression = new ConstantTableExpression();
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

    public function testCastExpressionWithValue(): void
    {
        $select = new SelectQuery();
        $select->columnExpression(
            new CastExpression(12, 'some_type', 'value_type')
        );

        self::assertSameSql(
            'select cast(? as some_type)',
            self::format($select)
        );
    }

    public function testCastExpressionWithExpression(): void
    {
        $select = new SelectQuery();
        $select->columnExpression(
            new CastExpression(
                new ConstantRowExpression(['bla', 12]),
                'some_type'
            )
        );

        self::assertSameSql(
            'select cast(row(?, ?) as some_type)',
            self::format($select)
        );
    }

    public function testCastExpressionWithExpressionInRow(): void
    {
        // This test is not necessary, but it correspond to a real life
        // use case, where the SQL server wrongly guess value types in
        // UNION query when one of the UNION queries is a constant table
        // expression (VALUES), just writing it for the posterity.
        $select = new SelectQuery('some_table', 'st');
        $select->columns(['st.a', 'st.b']);

        $other = new ConstantTableExpression();
        $other->row([
            new CastExpression('60a696b1-e600-4c82-9ee4-4d56601a9120', 'uuid'),
            'foo'
        ]);

        $select->union($other);

        self::assertSameSql(
            <<<'SQL'
            select "st"."a", "st"."b" from "some_table" as "st"
            union
            values
                (cast(? as uuid), ?)
            SQL,
            self::format($select)
        );
    }

    public function testCastExpressionWithExpressionWarnValueTypeWillBeIgnored(): void
    {
        $select = new SelectQuery();
        $select->columnExpression(
            new CastExpression(
                new ConstantRowExpression(['bla', 12]),
                'some_type',
                'value_type'
            )
        );

        self::assertSameSql(
            'select cast(row(?, ?) as some_type)',
            self::format($select)
        );

        self::markTestIncomplete("Warning is not implemented yet.");
    }

    public function testRawQueryGetsParsed(): void
    {
        $select = new SelectQuery('some_table');
        $select->columnExpression(new RawQuery('select ?::int', [1]));

        self::assertSameSql(
            'select (select ?) from "some_table"',
            self::format($select)
        );
    }

    public function testRawQueryWithExpressionArgumentAreExpanded(): void
    {
        $row = new ConstantRowExpression([1, 'foo']);

        $select = new SelectQuery('some_table');
        $select->whereExpression(new RawQuery('(foo, bar) = ?', $row));

        self::assertSameSql(
            'select * from "some_table" where (foo, bar) = (?, ?)',
            self::format($select)
        );
    }

    public function testRawExpressionGetsParsed(): void
    {
        $select = new SelectQuery('some_table');
        $select->columnExpression(new RawExpression('?::int', [1]));

        self::assertSameSql(
            'select ? from "some_table"',
            self::format($select)
        );
    }

    public function testRawExpressionWithExpressionArgumentAreExpanded(): void
    {
        $row = new ConstantRowExpression([1, 'foo']);

        $select = new SelectQuery('some_table');
        $select->whereExpression(new RawExpression('(foo, bar) = ?', $row));

        self::assertSameSql(
            'select * from "some_table" where (foo, bar) = (?, ?)',
            self::format($select)
        );
    }
}
