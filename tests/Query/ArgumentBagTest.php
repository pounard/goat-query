<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\ArgumentBag;
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

    public function testAddUsingValueRepresentation()
    {
        $this->markTestIncomplete("Implement me");

        $argumentBag = new ArgumentBag();
    }

    public function testAppend()
    {
        $this->markTestIncomplete("Implement me");
    }

    public function testAppendArrayWithNames()
    {
        $this->markTestIncomplete("Implement me");

        $argumentBag = new ArgumentBag();
    }
}
