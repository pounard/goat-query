<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\ArgumentBag;
use Goat\Query\ExpressionFactory;
use Goat\Query\ExpressionRaw;
use Goat\Query\ExpressionValue;
use Goat\Query\QueryError;
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
    public function testExpressionValue()
    {
        $simple = ExpressionValue::create(42);
        self::assertNull($simple->getType());
        self::assertSame(42, $simple->getValue());
        $arguments = $simple->getArguments();
        self::assertTrue($arguments instanceof ArgumentBag);
        self::assertSame([42], $arguments->getAll());

        $string = ExpressionValue::create('some:string:_ouy"" \\\\é \#\'jiretj @');
        self::assertNull($string->getType());
        self::assertSame('some:string:_ouy"" \\\\é \#\'jiretj @', $string->getValue());
        $arguments = $string->getArguments();
        self::assertTrue($arguments instanceof ArgumentBag);
        self::assertSame(['some:string:_ouy"" \\\\é \#\'jiretj @'], $arguments->getAll());
    }

    public function testExpressionFactoryWithColumnRaiseErrorIfNotScalar()
    {
        self::expectException(QueryError::class);
        self::expectExceptionMessageMatches('/column reference must be a string or an instance/');

        ExpressionFactory::column(new \DateTime());
    }

    public function testExpressionFactoryWithExpressionPassthrough()
    {
        $expression = ExpressionRaw::create('foo');
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

        ExpressionFactory::raw(ExpressionRaw::create('foo'), ['a']);
    }
}
