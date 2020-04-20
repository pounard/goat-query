<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Runner\Testing\NullEscaper;
use PHPUnit\Framework\TestCase;

class FormatterBaseRegexTest extends TestCase
{
    use BuilderTestTrait;

    /**
     * This is a very stupid yet necessary test.
     *
     * It is based upon a failing real life scenario.
     */
    public function testEscapeSequenceIsNonGreedy()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $prepared = $formatter->prepare(<<<SQL
select "foo" from "some_table" where "a" = ? and "b" = ?"
SQL
        );

        $argumentList = $prepared->getArgumentList();
        $this->assertSame(2, $argumentList->count());
    }

    public function testPlaceholderUnescaping(): void
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $prepared = $formatter->prepare(<<<SQL
select "foo" from "some_table" where "a" ?? "foo" and "b" = ?"
SQL
        );

        $argumentList = $prepared->getArgumentList();
        $this->assertSame(1, $argumentList->count());

        $this->assertSameSql(<<<SQL
select "foo" from "some_table" where "a" ? "foo" and "b" = #1"
SQL
            , $prepared->getRawSQL()
        );
    }

    /**
     * Simple named parameters test.
     */
    public function testNamedParametersAreFound()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $prepared = $formatter->prepare(<<<SQL
select "foo" from "some_table" where "a" = :foo and "b" = :bar and "c" = ?
SQL
        );

        $argumentList = $prepared->getArgumentList();
        $this->assertSame(3, $argumentList->count());
        $this->assertSame(0, $argumentList->getNameIndex('foo'));
        $this->assertSame(1, $argumentList->getNameIndex('bar'));
    }

    /**
     * Basic placeholder test.
     */
    public function testPlaceholderAreFound()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $prepared = $formatter->prepare(<<<SQL
select * from bar where foo = ?
SQL
        );

        $argumentList = $prepared->getArgumentList();
        $this->assertSame(1, $argumentList->count());
    }

    /**
     * Does a query will all 3 of a pgsql cast, a named parameter and
     * a typed named parameter. Things should not mix up.
     */
    public function testPgSqlCastDoesNotMixedUpWithNames()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $prepared = $formatter->prepare(<<<SQL
select *, a::int from bar where foo = :foo and bar = :bar::bigint
SQL
        );

        $argumentList = $prepared->getArgumentList();
        $this->assertSame(2, $argumentList->count());
        $this->assertSame(0, $argumentList->getNameIndex('foo'));
        $this->assertNull($argumentList->getTypeAt(0));
        $this->assertSame(1, $argumentList->getNameIndex('bar'));
        $this->assertSame('bigint', $argumentList->getTypeAt(1));
    }

    /**
     * Simple test where a placeholder is within an escaped sequence.
     */
    public function testPlaceholderInEscapeSequencesAreIgnored()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $prepared = $formatter->prepare(<<<SQL
select "oups ?" from bar where foo = 'some ? randomly placed'
SQL
        );

        $argumentList = $prepared->getArgumentList();
        $this->assertSame(0, $argumentList->count());
    }

    /**
     * Simple test where a named parameter is within an escaped sequence.
     */
    public function testNamedParametersInEscapeSequencesAreIgnored()
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $prepared = $formatter->prepare(<<<SQL
select ":oups" from bar where foo = 'some :param randomly placed'
SQL
        );

        $argumentList = $prepared->getArgumentList();
        $this->assertSame(0, $argumentList->count());
    }
}
