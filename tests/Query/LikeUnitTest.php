<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\QueryError;
use Goat\Query\Expression\LikeExpression;
use PHPUnit\Framework\TestCase;

final class LikeUnitTest extends TestCase
{
    use BuilderTestTrait;

    /**
     * MySQL does not support ILIKE.
     */
    public function testLikeMySql(): void
    {
        $expression = LikeExpression::iLike('some column', '%foo?_', 'b%a_r');

        self::assertSameSql("\"some column\" like '%foob\\%a\\_r_'", self::formatWith($expression, self::createMySQLWriter()));
    }

    /**
     * MySQL does not support ILIKE.
     */
    public function testNotLikeMySql(): void
    {
        $expression = LikeExpression::notILike('some column', '%foo?_', 'b%a_r');

        self::assertSameSql("\"some column\" not like '%foob\\%a\\_r_'", self::formatWith($expression, self::createMySQLWriter()));
    }

    /**
     * Basic like test.
     */
    public function testLikeWithValue(): void
    {
        $expression = LikeExpression::like('some column', '%foo?_', 'b%a_r');

        self::assertSameSql("\"some column\" like '%foob\\%a\\_r_'", self::format($expression));
    }

    /**
     * Test wildcard is configurable.
     */
    public function testLikeWithValueWithDifferentWildcard(): void
    {
        $expression = LikeExpression::like('some column', '%foo#BOUH#_', 'b%a_r', '#BOUH#');

        self::assertSameSql("\"some column\" like '%foob\\%a\\_r_'", self::format($expression));
    }

    /**
     * Test with no value, without value, wildcard is not removed.
     */
    public function testLikeWithoutValue(): void
    {
        $expression = LikeExpression::like('some column', '%foo?_');

        self::assertSameSql("\"some column\" like '%foo?_'", self::format($expression));
    }

    /**
     * Test that value without wildcard raise errors.
     */
    public function testLikeWithValueButNoWildcardRaiseError(): void
    {
        self::expectException(QueryError::class);

        LikeExpression::like('some column', '%foo_', 'some value');
    }

    /**
     * Test that value without wildcard (but different one) raise errors.
     */
    public function testLikeWithValueButNoWildcardRaiseErrorWithDifferentWildcard(): void
    {
        self::expectException(QueryError::class);

        LikeExpression::like('some column', '%foo?_', 'some value', 'bouya');
    }

    /**
     * Basic like test.
     */
    public function testNotLike(): void
    {
        $expression = LikeExpression::notLike('some column', '?%', 'b%ar');

        self::assertSameSql("\"some column\" not like 'b\\%ar%'", self::format($expression));
    }

    /**
     * Basic like test.
     */
    public function testILike(): void
    {
        $expression = LikeExpression::iLike('some column', '?%', 'b%ar');

        self::assertSameSql("\"some column\" ilike 'b\\%ar%'", self::format($expression));
    }

    /**
     * Basic like test.
     */
    public function testNotILike(): void
    {
        $expression = LikeExpression::notILike('some column', '?%', 'b%ar');

        self::assertSameSql("\"some column\" not ilike 'b\\%ar%'", self::format($expression));
    }
}
