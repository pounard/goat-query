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
        // Hence the follwing string:
        $test = '{"ah\\\\",a\'h,"a,h","a}h","a{h","a\\"h","ah\\"","a{}h","a{\\"ah\\"}h","a{\'ah\'}h","ah\\\\","a\\\\h"}';

        $this->assertSame([
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
        $test = '{{1,2,3},{{4,5,6},7,8}}';

        $this->assertSame([
            [
                "1", "2", "3"
            ],
            [
                ["4", "5", "6"],
                "7", "8"
            ]
        ], PgSQLParser::parseArray($test));
    }
}
