<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\ExpressionLike;
use Goat\Query\QueryError;
use PHPUnit\Framework\TestCase;

class LikeTest extends TestCase
{
    use BuilderTestTrait;

    /**
     * Basic like test
     */
    public function testLikeWithValue()
    {
        $formatter = $this->createStandardFormatter();
        $expression = ExpressionLike::like('some column', '%foo?_', 'b%a_r');

        $this->assertSameSql("\"some column\" like '%foob\\%a\\_r_'", $formatter->format($expression));
    }

    /**
     * Test wildcard is configurable
     */
    public function testLikeWithValueWithDifferentWildcard()
    {
        $formatter = $this->createStandardFormatter();
        $expression = ExpressionLike::like('some column', '%foo#BOUH#_', 'b%a_r', '#BOUH#');

        $this->assertSameSql("\"some column\" like '%foob\\%a\\_r_'", $formatter->format($expression));
    }

    /**
     * Test with no value, without value, wildcard is not removed
     */
    public function testLikeWithoutValue()
    {
        $formatter = $this->createStandardFormatter();
        $expression = ExpressionLike::like('some column', '%foo?_');

        $this->assertSameSql("\"some column\" like '%foo?_'", $formatter->format($expression));
    }

    /**
     * Test that value without wildcard raise errors
     */
    public function testLikeWithValueButNoWildcardRaiseError()
    {
        $this->expectException(QueryError::class);
        ExpressionLike::like('some column', '%foo_', 'some value');
    }

    /**
     * Test that value without wildcard (but different one) raise errors
     */
    public function testLikeWithValueButNoWildcardRaiseErrorWithDifferentWildcard()
    {
        $this->expectException(QueryError::class);
        ExpressionLike::like('some column', '%foo?_', 'some value', 'bouya');
    }

    /**
     * Basic like test
     */
    public function testNotLike()
    {
        $formatter = $this->createStandardFormatter();
        $expression = ExpressionLike::notLike('some column', '?%', 'b%ar');

        $this->assertSameSql("\"some column\" not like 'b\\%ar%'", $formatter->format($expression));
    }

    /**
     * Basic like test
     */
    public function testILike()
    {
        $formatter = $this->createStandardFormatter();
        $expression = ExpressionLike::iLike('some column', '?%', 'b%ar');

        $this->assertSameSql("\"some column\" ilike 'b\\%ar%'", $formatter->format($expression));
    }

    /**
     * Basic like test
     */
    public function testNotILike()
    {
        $formatter = $this->createStandardFormatter();
        $expression = ExpressionLike::notILike('some column', '?%', 'b%ar');

        $this->assertSameSql("\"some column\" not ilike 'b\\%ar%'", $formatter->format($expression));
    }
}
