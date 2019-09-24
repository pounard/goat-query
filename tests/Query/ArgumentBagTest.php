<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\ArgumentBag;
use Goat\Query\ExpressionValue;
use PHPUnit\Framework\TestCase;

class ArgumentBagTest extends TestCase
{
    public function testAdd()
    {
        $argumentBag = new ArgumentBag();
        $argumentBag->add(1);
        $argumentBag->add('foo', null, 'varchar');
        $argumentBag->add($date = new \DateTimeImmutable(), 'when_it_happened', 'datetime');

        // Values are OK
        $this->assertSame([1, 'foo', $date], $argumentBag->getAll());

        // Data is propagated to argument list
        $this->assertSame(null, $argumentBag->getTypeAt(0));
        $this->assertSame('varchar', $argumentBag->getTypeAt(1));
        $this->assertSame(2, $argumentBag->getNameIndex('when_it_happened'));
    }

    public function testAddUsingExpressionValue()
    {
        $argumentBag = new ArgumentBag();
        $argumentBag->add(1);
        $argumentBag->add(ExpressionValue::create('a', 'some_type'));
        $argumentBag->add(ExpressionValue::create('b', 'other_type'));

        $this->assertSame(null, $argumentBag->getTypeAt(0));
        $this->assertSame('some_type', $argumentBag->getTypeAt(1));
    }

    public function testAppend()
    {
        $this->markTestIncomplete("Implement me");
    }

    public function testAppendArrayWithNames()
    {
        $this->markTestIncomplete("Implement me");
    }
}
