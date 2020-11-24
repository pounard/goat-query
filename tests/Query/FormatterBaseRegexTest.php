<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Runner\Testing\NullEscaper;
use PHPUnit\Framework\TestCase;

final class FormatterBaseRegexTest extends TestCase
{
    use BuilderTestTrait;

    /**
     * This is a very stupid yet necessary test.
     *
     * It is based upon a failing real life scenario.
     */
    public function testEscapeSequenceIsNonGreedy(): void
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $prepared = $formatter->prepare(
            <<<SQL
            select "foo" from "some_table" where "a" = ? and "b" = ?"
            SQL
        );

        self::assertCount(2, $prepared->getArgumentTypes());
    }

    public function testPlaceholderUnescaping(): void
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $prepared = $formatter->prepare(
            <<<SQL
            select "foo" from "some_table" where "a" ?? "foo" and "b" = ?::date and "c" = ?
            SQL
        );

        self::assertCount(2, $prepared->getArgumentTypes());
        self::assertSame('date', $prepared->getArgumentTypes()[0]);
        self::assertNull($prepared->getArgumentTypes()[1]);

        self::assertSameSql(
            <<<SQL
            select "foo" from "some_table" where "a" ? "foo" and "b" = #1 and "c" = #2
            SQL,
            $prepared->toString()
        );
    }

    /**
     * Simple named parameters test.
     */
    public function testNamedParametersAreIgnoredFound(): void
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $prepared = $formatter->prepare(
            <<<SQL
            select "foo" from "some_table" where "a" = :foo and "b" = :bar and "c" = ?
            SQL
        );

        self::assertCount(1, $prepared->getArgumentTypes());
    }

    /**
     * Basic placeholder test.
     */
    public function testPlaceholderAreFound(): void
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $prepared = $formatter->prepare(<<<SQL
select * from bar where foo = ?
SQL
        );

        self::assertCount(1, $prepared->getArgumentTypes());
    }

    /**
     * Simple test where a placeholder is within an escaped sequence.
     */
    public function testPlaceholderInEscapeSequencesAreIgnored(): void
    {
        $formatter = new FooSqlWriter(new NullEscaper(true));

        $prepared = $formatter->prepare(<<<SQL
select "oups ?" from bar where foo = 'some ? randomly placed'
SQL
        );

        self::assertCount(0, $prepared->getArgumentTypes());
    }
}
