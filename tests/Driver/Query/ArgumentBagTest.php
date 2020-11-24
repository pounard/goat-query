<?php

declare(strict_types=1);

namespace Goat\Driver\Tests\Query;

use Goat\Driver\Query\ArgumentBag;
use Goat\Query\Expression\ValueExpression;
use PHPUnit\Framework\TestCase;

class ArgumentBagTest extends TestCase
{
    public function testAdd(): void
    {
        $argumentBag = new ArgumentBag();
        $argumentBag->add(1);
        $argumentBag->add('foo', 'varchar');
        $argumentBag->add($date = new \DateTimeImmutable(), 'datetime');

        // Values are OK
        self::assertSame([1, 'foo', $date], $argumentBag->getAll());

        // Data is propagated to argument list
        self::assertSame(null, $argumentBag->getTypeAt(0));
        self::assertSame('varchar', $argumentBag->getTypeAt(1));
    }

    public function testAddUsingValueExpression(): void
    {
        $argumentBag = new ArgumentBag();
        $argumentBag->add(1);
        $argumentBag->add(new ValueExpression('a', 'some_type'));
        $argumentBag->add(new ValueExpression('b', 'other_type'));

        self::assertSame(null, $argumentBag->getTypeAt(0));
        self::assertSame('some_type', $argumentBag->getTypeAt(1));
    }
}
