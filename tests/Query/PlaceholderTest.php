<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Converter\DefaultConverter;
use Goat\Query\ArgumentBag;
use Goat\Query\SelectQuery;
use PHPUnit\Framework\TestCase;

class PlaceholderTest extends TestCase
{
    use BuilderTestTrait;

    public function testArbitraryPgTypeCastAreNotConverted()
    {
        $formatter = new FooFormatter(new NullEscaper(true));
        $formatter->setConverter(new DefaultConverter());

        $formatted = $formatter->prepare(
            'select * from some_table where foo::date = ?::date',
            [\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1983-03-22 08:25:00')]
        );

        $this->assertSameSql('select * from some_table where foo::date = cast(#1 as date)', $formatted->getQuery());
        $this->assertSame(['1983-03-22'], $formatted->getArguments());
    }

    public function testNamedParametersWithoutTypeAreReplaced()
    {
        $formatter = new FooFormatter(new NullEscaper(true));
        $formatter->setConverter(new DefaultConverter());

        $formatted = $formatter->prepare(
            'select * from some_table where foo = :date',
            [\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1983-03-22 08:25:00')]
        );

        // Type is guessed as 'timestamp'
        $this->assertSameSql('select * from some_table where foo = #1', $formatted->getQuery());
        $this->assertSame(['1983-03-22 08:25:00'], $formatted->getArguments());
    }

    public function testNamedParametersWithTypeAreReplacedAndTyped()
    {
        $formatter = new FooFormatter(new NullEscaper(true));
        $formatter->setConverter(new DefaultConverter());

        $formatted = $formatter->prepare(
            'select * from some_table where foo = :date::date',
            [\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1983-03-22 08:25:00')]
        );

        // Type is guessed as 'timestamp'
        $this->assertSameSql('select * from some_table where foo = cast(#1 as date)', $formatted->getQuery());
        $this->assertSame(['1983-03-22'], $formatted->getArguments());
    }

    public function testCastWithConverterAndTypeInQuery()
    {
        $formatter = new FooFormatter(new NullEscaper(true));
        $formatter->setConverter(new DefaultConverter());

        $formatted = $formatter->prepare(
            'select * from some_table where foo = ?::timestamp',
            [\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1983-03-22 08:25:00')]
        );

        $this->assertSameSql('select * from some_table where foo = cast(#1 as timestamp)', $formatted->getQuery());
        $this->assertSame(['1983-03-22 08:25:00'], $formatted->getArguments());
    }

    public function testWithConverterAndTypeGuessShouldNotCast()
    {
        $formatter = new FooFormatter(new NullEscaper(true));
        $formatter->setConverter(new DefaultConverter());

        $formatted = $formatter->prepare(
            'select * from some_table where foo = ?',
            [\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1983-03-22 08:25:00')]
        );

        $this->assertSameSql('select * from some_table where foo = #1', $formatted->getQuery());
        $this->assertSame(['1983-03-22 08:25:00'], $formatted->getArguments());
    }

    public function testCastWithConverterAndTypeFromBag()
    {
        $formatter = new FooFormatter(new NullEscaper(true));
        $formatter->setConverter(new DefaultConverter());

        $arguments =  new ArgumentBag();
        $arguments->add(\DateTimeImmutable::createFromFormat('Y-m-d', '1983-03-22'), null, 'date');

        $formatted = $formatter->prepare(
            'select * from some_table where foo = ?',
            $arguments
        );

        $this->assertSameSql('select * from some_table where foo = cast(#1 as date)', $formatted->getQuery());
        $this->assertSame(['1983-03-22'], $formatted->getArguments());
    }

    public function testWithConverterAndTypeInQuery()
    {
        $formatter = new FooFormatter(new NullEscaper(true));
        $formatter->setConverter(new DefaultConverter());

        $formatted = $formatter->prepare(
            'select * from some_table where foo = ?::bool',
            [true]
        );

        $this->assertSameSql('select * from some_table where foo = #1', $formatted->getQuery());
        $this->assertSame(['t'], $formatted->getArguments());
    }

    public function testWithConverterAndTypeFromBag()
    {
        $formatter = new FooFormatter(new NullEscaper(true));
        $formatter->setConverter(new DefaultConverter());

        $arguments =  new ArgumentBag();
        $arguments->add(true, null, 'bool');

        $formatted = $formatter->prepare(
            'select * from some_table where foo = ?',
            $arguments
        );

        $this->assertSameSql('select * from some_table where foo = #1', $formatted->getQuery());
        $this->assertSame(['t'], $formatted->getArguments());
    }

    public function testWithoutConverter()
    {
        $formatter = new FooFormatter(new NullEscaper(true));

        $builder = new SelectQuery('some_table');
        $builder->condition('foo', 7);
        $builder->expression('bar = ?', "12");

        $query = $formatter->prepare($builder);
        $this->assertSameSql('select * from "some_table" where "foo" = #1 and bar = #2', $query->getQuery());
        // Parameters are converted too.
        $this->assertSame([7, "12"], $query->getArguments());
    }

    /*
    public function testNamedPlaceholder()
    {
        $formatter = new FooFormatter(new NullEscaper(true));

        $builder = new SelectQuery('some_table');
        $builder->condition('foo', 7);
        $builder->expression('bar = ?', 12);

        $query = $formatter->prepare($builder);

        $this->assertSameSql('select * from "some_table" where "foo" = #1 and bar = #2', $query->getQuery());
        $this->assertSame([7, 12], $query->getArguments());
    }
     */

    public function testEscapedPlaceholderIsIgnored()
    {
        $formatter = new FooFormatter(new NullEscaper(true));

        // Query builder in parameters escaping
        $builder = new SelectQuery('some_table');
        $builder->condition('foo', 'pouet ?');
        $builder->condition('bar ?', 12);

        $query = $formatter->prepare($builder);
        $this->assertSameSql('select * from "some_table" where "foo" = #1 and "bar ?" = #2', $query->getQuery());

        // String that contains all three escape sequences and one parameter
        $query = $formatter->prepare("select '?' from \"weird ? table\" where bar = ? and foo = $$?$$ and john = $$ doh ? $$", ['test']);
        $this->assertSameSql("select '?' from \"weird ? table\" where bar = #1 and foo = $$?$$ and john = $$ doh ? $$", $query->getQuery());
    }

    /*
    public function testEscapedNamedPlaceholderIsIgnored()
    {
        
    }
     */
}
