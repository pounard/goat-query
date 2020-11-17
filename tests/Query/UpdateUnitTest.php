<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\ExpressionRaw;
use Goat\Query\QueryError;
use Goat\Query\UpdateQuery;
use PHPUnit\Framework\TestCase;

final class UpdateUnitTest extends TestCase
{
    use BuilderTestTrait;

    public function testCoverageForClone(): void
    {
        $query = new UpdateQuery('a');
        $query->from('b');
        $query->join('c');
        $query->set('foo', 'bar');
        $query->createWith('bar', 'baz');

        $cloned = clone $query;

        self::assertSameSql(
            self::format($query),
            self::format($cloned)
        );
    }

    public function testNoSetRaiseError(): void
    {
        $query = new UpdateQuery('bar');

        self::expectException(QueryError::class);
        self::expectDeprecationMessageMatches('/without any columns to update/');

        self::format($query);
    }

    public function testSetUpdate(): void
    {
        $query = new UpdateQuery('a');
        $query->sets([
            'fizz' => 42,
            'buzz' => 666,
        ]);
        $query->set('foo', 'bar');

        self::assertSameSql(
            'update "a" set "fizz" = ?, "buzz" = ?, "foo" = ?',
            self::format($query)
        );
    }

    public function testSetWithExpression(): void
    {
        $query = new UpdateQuery('a');
        $query->set('foo', ExpressionRaw::create('bla()'));

        self::assertSameSql(
            'update "a" set "foo" = bla()',
            self::format($query)
        );
    }
}
