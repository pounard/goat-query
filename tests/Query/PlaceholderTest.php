<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Driver\Query\ArgumentBag;
use Goat\Query\SelectQuery;
use Goat\Runner\Testing\NullEscaper;
use PHPUnit\Framework\TestCase;

class PlaceholderTest extends TestCase
{
    use BuilderTestTrait;

    public function testArbitraryPgTypeCastAreNotConverted()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $formatted = $formatter->prepare(
            'select * from some_table where foo::date = ?::date',
            [\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1983-03-22 08:25:00')]
        );

        $this->assertSameSql('select * from some_table where foo::date = #1', $formatted->getRawSQL());
        // @todo FIXME $this->assertSame(['1983-03-22'], $formatted->getArguments());
    }

    public function testCastWithConverterAndTypeInQuery()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $formatted = $formatter->prepare(
            'select * from some_table where foo = ?::timestamp',
            [\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1983-03-22 08:25:00')]
        );

        $this->assertSameSql('select * from some_table where foo = #1', $formatted->getRawSQL());
        // @todo FIXME $this->assertSame(['1983-03-22 08:25:00'], $formatted->getArguments());
    }

    public function testWithConverterAndTypeGuessShouldNotCast()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $formatted = $formatter->prepare(
            'select * from some_table where foo = ?',
            [\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1983-03-22 08:25:00')]
        );

        $this->assertSameSql('select * from some_table where foo = #1', $formatted->getRawSQL());
        // @todo FIXME $this->assertSame(['1983-03-22 08:25:00'], $formatted->getArguments());
    }

    public function testCastWithConverterAndTypeFromBag()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $arguments =  new ArgumentBag();
        $arguments->add(\DateTimeImmutable::createFromFormat('Y-m-d', '1983-03-22'), null, 'date');

        $formatted = $formatter->prepare(
            'select * from some_table where foo = ?',
            $arguments
        );

        $this->assertSameSql('select * from some_table where foo = #1', $formatted->getRawSQL());
        // @todo FIXME $this->assertSame(['1983-03-22'], $formatted->getArguments());
    }

    public function testWithConverterAndTypeInQuery()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $formatted = $formatter->prepare(
            'select * from some_table where foo = ?::bool',
            [true]
        );

        $this->assertSameSql('select * from some_table where foo = #1', $formatted->getRawSQL());
        // @todo FIXME $this->assertSame(['t'], $formatted->getArguments());
    }

    public function testWithConverterAndTypeFromBag()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $arguments =  new ArgumentBag();
        $arguments->add(true, null, 'bool');

        $formatted = $formatter->prepare(
            'select * from some_table where foo = ?',
            $arguments
        );

        $this->assertSameSql('select * from some_table where foo = #1', $formatted->getRawSQL());
        // @todo FIXME $this->assertSame(['t'], $formatted->getArguments());
    }

    public function testWithoutConverter()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $builder = new SelectQuery('some_table');
        $builder->where('foo', 7);
        $builder->whereExpression('bar = ?', "12");

        $query = $formatter->prepare($builder);
        $this->assertSameSql('select * from "some_table" where "foo" = #1 and bar = #2', $query->getRawSQL());
        // Parameters are converted too.
        // @todo FIXME $this->assertSame([7, "12"], $query->getArguments());
    }

    public function testEscapedPlaceholderIsIgnored()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        // Query builder in parameters escaping
        $builder = new SelectQuery('some_table');
        $builder->where('foo', 'pouet ?');
        $builder->where('bar ?', 12);

        $query = $formatter->prepare($builder);
        $this->assertSameSql('select * from "some_table" where "foo" = #1 and "bar ?" = #2', $query->getRawSQL());

        // String that contains all three escape sequences and one parameter
        $query = $formatter->prepare("select '?' from \"weird ? table\" where bar = ? and foo = $$?$$ and john = $$ doh ? $$", ['test']);
        $this->assertSameSql("select '?' from \"weird ? table\" where bar = #1 and foo = $$?$$ and john = $$ doh ? $$", $query->getRawSQL());
    }
}
