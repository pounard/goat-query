<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\SelectQuery;
use Goat\Runner\Testing\NullEscaper;
use PHPUnit\Framework\TestCase;

class PlaceholderTest extends TestCase
{
    use BuilderTestTrait;

    public function testArbitraryPgTypeCastAreNotConverted(): void
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));
        $formatted = $formatter->prepare('select * from some_table where foo::date = ?::date');

        self::assertSameSql('select * from some_table where foo::date = #1', $formatted->toString());
    }

    public function testCastWithConverterAndTypeInQuery(): void
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));
        $formatted = $formatter->prepare('select * from some_table where foo = ?::timestamp');

        self::assertSameSql('select * from some_table where foo = #1', $formatted->toString());
    }

    public function testWithConverterAndTypeGuessShouldNotCast(): void
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));
        $formatted = $formatter->prepare('select * from some_table where foo = ?');

        self::assertSameSql('select * from some_table where foo = #1', $formatted->toString());
    }

    public function testWithConverterAndTypeInQuery(): void
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));
        $formatted = $formatter->prepare('select * from some_table where foo = ?::bool');

        self::assertSameSql('select * from some_table where foo = #1', $formatted->toString());
    }

    public function testWithoutConverter(): void
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $builder = new SelectQuery('some_table');
        $builder->where('foo', 7);
        $builder->whereExpression('bar = ?', "12");

        $query = $formatter->prepare($builder);
        self::assertSameSql('select * from "some_table" where "foo" = #1 and bar = #2', $query->toString());
    }

    public function testEscapedPlaceholderIsIgnored(): void
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        // Query builder in parameters escaping
        $builder = new SelectQuery('some_table');
        $builder->where('foo', 'pouet ?');
        $builder->where('bar ?', 12);

        $query = $formatter->prepare($builder);
        self::assertSameSql('select * from "some_table" where "foo" = #1 and "bar ?" = #2', $query->toString());

        // String that contains all three escape sequences and one parameter
        $query = $formatter->prepare("select '?' from \"weird ? table\" where bar = ? and foo = $$?$$ and john = $$ doh ? $$");
        self::assertSameSql("select '?' from \"weird ? table\" where bar = #1 and foo = $$?$$ and john = $$ doh ? $$", $query->toString());
    }
}
