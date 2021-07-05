<?php

declare(strict_types=1);

namespace Goat\Converter\Tests;

use Goat\Converter\Driver\PgSQLParser;
use PHPUnit\Framework\TestCase;

class PgSQLParserTest extends TestCase
{
    public function testParseArrayWithUglyStrings()
    {
        // SELECT ARRAY['ah\', 'a''h', 'a,h', 'a}h', 'a{h', 'a"h', 'ah"', 'a{}h', 'a{"ah"}h', 'a{''ah''}h', 'ah\', 'a\h'];
        // Will return: {"ah\\", a'h,"a,h","a}h","a{h","a\"h","ah\"","a{}h","a{\"ah\"}h","a{'ah'}h","ah\\","a\\h"}
        $test = <<<'SQL'
            {"ah\\",a'h,"a,h","a}h","a{h","a\"h","ah\"","a{}h","a{\"ah\"}h","a{'ah'}h","ah\\","a\\h"}
            SQL
        ;

        self::assertSame([
            'ah\\',
            'a\'h',
            'a,h',
            'a}h',
            'a{h',
            'a"h',
            'ah"',
            'a{}h',
            'a{"ah"}h',
            'a{\'ah\'}h',
            'ah\\',
            'a\\h',
        ], PgSQLParser::parseArray($test));
    }

    public function testParseArrayNested()
    {
        // This is not valid considering pgsql typing, but parser is loose and flexible.
        // Be liberal in what you accept.
        self::assertSame([
            [
                "1", "2", "3"
            ],
            [
                ["4", "5", "6"],
                "7", "8"
            ]
        ], PgSQLParser::parseArray(
            <<<'SQL'
            {{1,2,3},{{4,5,6},7,8}}
            SQL
        ));
    }

    public function testParseRowWithUglyStrings()
    {
        // SELECT ROW('ah\', 'a''h', 'a,h', 'a}h', 'a{h', 'a"h', 'ah"', 'a{}h', 'a{"ah"}h', 'a{''ah''}h', 'ah\', 'a\h');
        // Will return: ("ah\\",a'h,"a,h",a}h,a{h,"a""h","ah""",a{}h,"a{""ah""}h",a{'ah'}h,"ah\\","a\\h")
        $test = <<<'SQL'
            ("ah\\",a'h,"a,h","a}h","a{h","a\"h","ah\"","a{}h","a{\"ah\"}h","a{'ah'}h","ah\\","a\\h")
            SQL
        ;

        self::assertSame([
            'ah\\',
            'a\'h',
            'a,h',
            'a}h',
            'a{h',
            'a"h',
            'ah"',
            'a{}h',
            'a{"ah"}h',
            'a{\'ah\'}h',
            'ah\\',
            'a\\h',
        ], PgSQLParser::parseRow($test));
    }

    public function testParseRowWithInt(): void
    {
        self::assertSame(
            ["1", "2", "3"],
            PgSQLParser::parseRow(
                <<<'SQL'
                (1,2,3)
                SQL
            )
        );
    }

    public function testParseRowWithFloat(): void
    {
        self::assertSame(
            ["1.2", "2.3", "3.4"],
            PgSQLParser::parseRow(
                <<<'SQL'
                (1.2,2.3,3.4)
                SQL
            )
        );
    }

    public function testParseRowWithNested(): void
    {
        // This is not valid considering pgsql typing, but parser is loose and flexible.
        // Be liberal in what you accept.
        self::assertSame([
            [
                "1", "2", "3"
            ],
            [
                ["4", "5", "6"],
                "7", "8"
            ]
        ], PgSQLParser::parseRow(
            <<<'SQL'
            ((1,2,3),((4,5,6),7,8))
            SQL
        ));
    }

    public function testParseArrayRaiseErrorWhenNotStartingWith(): void
    {
        self::expectExceptionMessageMatches('/malformed input: array must be enclosed using {}/');
        PgSQLParser::parseArray('1,2}');
    }

    public function testParseArrayRaiseErrorWhenNotEndingWith(): void
    {
        self::expectExceptionMessageMatches('/malformed input: array must be enclosed using {}/');
        PgSQLParser::parseArray('{1,2');
    }

    public function testParseRowRaiseErrorWhenNotStartingWith(): void
    {
        self::expectExceptionMessageMatches('/malformed input: row must be enclosed using ()/');
        PgSQLParser::parseRow('1,2)');
    }

    public function testParseRowRaiseErrorWhenNotEndingWith(): void
    {
        self::expectExceptionMessageMatches('/malformed input: row must be enclosed using ()/');
        PgSQLParser::parseRow('{1,2');
    }
}
