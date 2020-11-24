<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\ExpressionFactory;
use Goat\Query\QueryError;
use Goat\Query\Expression\RawExpression;
use Goat\Query\Expression\ValueExpression;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
{
    use BuilderTestTrait;

    /**
     * Test argument bag behaviour
     */
    public function testArgumentBag()
    {
        self::markTestIncomplete("not implemented yet");
    }

    /**
     * Test expression value object
     */
    public function testValueExpression()
    {
        $simple = new ValueExpression(42);
        self::assertNull($simple->getType());
        self::assertSame(42, $simple->getValue());

        $string = new ValueExpression('some:string:_ouy"" \\\\é \#\'jiretj @');
        self::assertNull($string->getType());
        self::assertSame('some:string:_ouy"" \\\\é \#\'jiretj @', $string->getValue());
    }

    public function testExpressionFactoryWithColumnRaiseErrorIfNotScalar()
    {
        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/column reference must be a string or an instance/');

        ExpressionFactory::column(new \DateTime());
    }

    public function testExpressionFactoryWithExpressionPassthrough()
    {
        $expression = new RawExpression('foo');
        $returned = ExpressionFactory::raw($expression);
        self::assertSame($expression, $returned);
    }

    public function testExpressionFactoryWithExpressionRaiseErrorIfNotScalar()
    {
        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/raw expression must be a scalar or an instance/');

        ExpressionFactory::raw(new \DateTime());
    }

    public function testExpressionFactoryWithExpressionRaiseErrorIfArguments()
    {
        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/instance and arguments along/');

        ExpressionFactory::raw(new RawExpression('foo'), ['a']);
    }
}
