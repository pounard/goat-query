<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\ExpressionLike;
use Goat\Query\QueryError;
use PHPUnit\Framework\TestCase;

class LikeUnitTest extends TestCase
{
    use BuilderTestTrait;

    /**
     * Basic like test.
     */
    public function testLikeWithValue(): void
    {
        $expression = ExpressionLike::like('some column', '%foo?_', 'b%a_r');

        self::assertSameSql("\"some column\" like '%foob\\%a\\_r_'", self::format($expression));
    }

    /**
     * Test wildcard is configurable.
     */
    public function testLikeWithValueWithDifferentWildcard(): void
    {
        $expression = ExpressionLike::like('some column', '%foo#BOUH#_', 'b%a_r', '#BOUH#');

        self::assertSameSql("\"some column\" like '%foob\\%a\\_r_'", self::format($expression));
    }

    /**
     * Test with no value, without value, wildcard is not removed.
     */
    public function testLikeWithoutValue(): void
    {
        $expression = ExpressionLike::like('some column', '%foo?_');

        self::assertSameSql("\"some column\" like '%foo?_'", self::format($expression));
    }

    /**
     * Test that value without wildcard raise errors.
     */
    public function testLikeWithValueButNoWildcardRaiseError(): void
    {
        self::expectException(QueryError::class);

        ExpressionLike::like('some column', '%foo_', 'some value');
    }

    /**
     * Test that value without wildcard (but different one) raise errors.
     */
    public function testLikeWithValueButNoWildcardRaiseErrorWithDifferentWildcard(): void
    {
        self::expectException(QueryError::class);

        ExpressionLike::like('some column', '%foo?_', 'some value', 'bouya');
    }

    /**
     * Basic like test.
     */
    public function testNotLike(): void
    {
        $expression = ExpressionLike::notLike('some column', '?%', 'b%ar');

        self::assertSameSql("\"some column\" not like 'b\\%ar%'", self::format($expression));
    }

    /**
     * Basic like test.
     */
    public function testILike(): void
    {
        $expression = ExpressionLike::iLike('some column', '?%', 'b%ar');

        self::assertSameSql("\"some column\" ilike 'b\\%ar%'", self::format($expression));
    }

    /**
     * Basic like test.
     */
    public function testNotILike(): void
    {
        $expression = ExpressionLike::notILike('some column', '?%', 'b%ar');

        self::assertSameSql("\"some column\" not ilike 'b\\%ar%'", self::format($expression));
    }
}
