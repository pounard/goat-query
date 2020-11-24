<?php

declare(strict_types=1);

namespace Goat\Driver\Tests\Query;

use Goat\Driver\Query\ArgumentBag;
use Goat\Query\Expression\ValueExpression;
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

    public function testAddUsingValueExpression()
    {
        $argumentBag = new ArgumentBag();
        $argumentBag->add(1);
        $argumentBag->add(new ValueExpression('a', 'some_type'));
        $argumentBag->add(new ValueExpression('b', 'other_type'));

        $this->assertSame(null, $argumentBag->getTypeAt(0));
        $this->assertSame('some_type', $argumentBag->getTypeAt(1));
    }
}
