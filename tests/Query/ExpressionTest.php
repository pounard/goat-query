<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\ArgumentBag;
use Goat\Query\ExpressionValue;
use PHPUnit\Framework\TestCase;

class BuilderExpresionTest extends TestCase
{
    use BuilderTestTrait;

    /**
     * Test argument bag behaviour
     */
    public function testArgumentBag()
    {
        $this->markTestIncomplete("not implemented yet");
    }

    /**
     * Test expression value object
     */
    public function testExpressionValue()
    {
        $simple = ExpressionValue::create(42);
        $this->assertNull($simple->getName());
        $this->assertNull($simple->getType());
        $this->assertSame(42, $simple->getValue());
        $arguments = $simple->getArguments();
        $this->assertTrue($arguments instanceof ArgumentBag);
        $this->assertSame([42], $arguments->getAll());

        $string = ExpressionValue::create('some:string:_ouy"" \\\\é \#\'jiretj @');
        $this->assertNull($string->getName());
        $this->assertNull($string->getType());
        $this->assertSame('some:string:_ouy"" \\\\é \#\'jiretj @', $string->getValue());
        $arguments = $string->getArguments();
        $this->assertTrue($arguments instanceof ArgumentBag);
        $this->assertSame(['some:string:_ouy"" \\\\é \#\'jiretj @'], $arguments->getAll());

        $named = ExpressionValue::create(':some_value_name');
        $this->assertSame('some_value_name', $named->getName());
        $this->assertNull($named->getType());
        $this->assertNull($named->getValue());
        $arguments = $named->getArguments();
        $this->assertTrue($arguments instanceof ArgumentBag);
        $this->assertSame([null], $arguments->getAll());

        $notTyped = ExpressionValue::create('42::integer');
        $this->assertNull($notTyped->getName());
        $this->assertSame(null, $notTyped->getType());
        $this->assertSame('42::integer', $notTyped->getValue());
        $arguments = $notTyped->getArguments();
        $this->assertTrue($arguments instanceof ArgumentBag);
        $this->assertSame(['42::integer'], $arguments->getAll());

        $namedAndTyped = ExpressionValue::create(':some_value::integer');
        $this->assertSame('some_value', $namedAndTyped->getName());
        $this->assertSame('integer', $namedAndTyped->getType());
        $this->assertNull($namedAndTyped->getValue());
        $arguments = $namedAndTyped->getArguments();
        $this->assertTrue($arguments instanceof ArgumentBag);
        $this->assertSame([null], $arguments->getAll());
    }
}
