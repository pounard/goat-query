<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\SelectQuery;
use Goat\Query\Where;
use Goat\Query\Expression\ColumnExpression;
use PHPUnit\Framework\TestCase;

class WhereFunctionalTest extends TestCase
{
    use BuilderTestTrait;

    public function testWhere(): void
    {
        $select = new SelectQuery('the_universe', 'u');
        $select->column('id');
        $select->where('id', new ColumnExpression('parent.id'));

        $where = (new Where())
            // Simple '<>' operator
            ->condition('foo', 'bar', Where::NOT_EQUAL)
            // Simple '=' operator
            ->condition('foo', 'foo')
            // Will turn into a 'in' operator
            ->condition('baz', [1, 2, 3])
            // Between and not between operators
            ->condition('range_a', [12, 24], Where::BETWEEN)
            ->condition('range_b', [48, 96], Where::NOT_BETWEEN)
            // Expliciti 'not in' operator
            ->condition('baz', [4, 5, 6], Where::NOT_IN)
            // We will build something here
            ->open(Where::OR)
                // Arbitrary operator, should work too
                ->condition('theWorld', 'enough', 'is not')
                ->expression('count(theWorld) = ?::int4', [1])
                // Parenthesis inside parenthesis is recursive
                // By the way, default is AND
                ->open()
                    ->expression('1 = ?', 0)
                    ->expression('2 * 2 = ?', 5)
                ->close()
            // Close parenthesis
            ->close()
            ->open()
                // Between and not between
                ->condition('beta', [37, 42], Where::BETWEEN)
                ->condition('gamma', [123, 234], Where::NOT_BETWEEN)
            ->close()
            ->open()
                // Comparisons
                ->condition('a', -66, Where::GREATER)
                ->condition('b', -67, Where::GREATER_OR_EQUAL)
                ->condition('c', -68, Where::LESS)
                ->condition('d', -69, Where::LESS_OR_EQUAL)
            ->close()
            ->isNull('roger')
            ->condition('tabouret', 'cassoulet')
            ->open(Where::OR)
                ->condition('test', 1)
                ->condition('other', ['this', 'is', 'an array'])
            ->close()
            ->exists($select)
            ->condition('universe_id', $select);
        ;

        $reference = <<<EOT
"foo" <> ?
and "foo" = ?
and "baz" in (?, ?, ?)
and "range_a" between ? and ?
and "range_b" not between ? and ?
and "baz" not in (?, ?, ?)
and (
    "theWorld" is not ?
    or count(theWorld) = ?::int4
    or (
        1 = ?
        and 2 * 2 = ?
    )
)
and (
    "beta" between ? and ?
    and "gamma" not between ? and ?
)
and (
    "a" > ?
    and "b" >= ?
    and "c" < ?
    and "d" <= ?
)
and "roger" is null
and "tabouret" = ?
and (
    "test" = ?
    or "other" in (?, ?, ?)
)
and exists (
    select "id" from "the_universe" as "u"
        where "id" = "parent"."id"
)
and "universe_id" in (
    select "id" from "the_universe" as "u"
        where "id" = "parent"."id"
)
EOT;

        self::assertSameSql($reference, self::format($where));

        // And now the exact same where, using convenience methods
        $where = (new Where())
            ->isNotEqual('foo', 'bar')
            ->isEqual('foo', 'foo')
            ->isEqual('baz', [1, 2, 3])
            ->isBetween('range_a', 12, 24)
            ->isNotBetween('range_b', 48, 96)
            ->isNotIn('baz', [4, 5, 6])
            ->or()
                // Custom operator cannot have a convenience method
                ->condition('theWorld', 'enough', 'is not')
                // Statement is statement, yield no surprises
                ->expression('count(theWorld) = ?::int4', [1])
                ->and()
                    ->expression('1 = ?', 0)
                    ->expression('2 * 2 = ?', 5)
                ->end()
            ->end()
            ->and()
                ->isBetween('beta', 37, 42)
                ->isNotBetween('gamma', 123, 234)
            ->end()
            ->and()
                ->isGreater('a', -66)
                ->isGreaterOrEqual('b', -67)
                ->isLess('c', -68)
                ->isLessOrEqual('d', -69)
            ->end()
            ->isNull('roger')
            ->isEqual('tabouret', 'cassoulet')
            ->or()
                ->isEqual('test', 1)
                ->isIn('other', ['this', 'is', 'an array'])
            ->end()
            ->exists($select)
            ->condition('universe_id', $select);
        ;

        // Expected is the exact same
        self::assertSameSql($reference, self::format($where));
    }

    public function testLike(): void
    {
        $where = (new Where())->isLike('a', 'a?%', 'b');
        self::assertSameSql('"a" like \'ab%\'', self::format($where));

        $where = (new Where())->isNotLike('a', 'a?%', 'b');
        self::assertSameSql('"a" not like \'ab%\'', self::format($where));

        $where = (new Where())->isLikeInsensitive('a', 'a?%', 'b');
        self::assertSameSql('"a" ilike \'ab%\'', self::format($where));

        $where = (new Where())->isNotLikeInsensitive('a', 'a?%', 'b');
        self::assertSameSql('"a" not ilike \'ab%\'', self::format($where));
    }

    public function testWhereWhenEmpty(): void
    {
        $where = (new Where());

        // Where is empty
        self::assertTrue($where->isEmpty());
        self::assertSameSql("1", self::format($where));

        // Where is not empty anymore
        $where->isNotNull('a');
        self::assertFalse($where->isEmpty());
        self::assertSameSql("\"a\" is not null", self::format($where));

        // Statement is empty
        $statement = $where->and();
        self::assertTrue($statement->isEmpty());
        self::assertSameSql("1", self::format($statement));

        // Statement is ignored, because empty
        self::assertSameSql("\"a\" is not null", self::format($where));
    }
}
