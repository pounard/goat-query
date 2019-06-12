<?php

declare(strict_types=1);

namespace Goat\Converter\Tests;

use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;
use Goat\Converter\Impl\IntervalValueConverter;
use PHPUnit\Framework\TestCase;

class DefaultConverterTest extends TestCase
{
    const TYPES_INT = [
        'bigserial', 'serial', 'serial2', 'serial4', 'serial8', 'smallserial',
        'bigint', 'int', 'int2', 'int4', 'int8', 'integer', 'smallint',
    ];

    const TYPES_STRING = [
        'char', 'character', 'clog', 'text', 'varchar'
    ];

    const TYPES_NUMERIC = [
        'decimal', 'double', 'float4', 'float8', 'numeric', 'real'
    ];

    public function testNullConversion()
    {
        $converter = new DefaultConverter();

        $this->assertNull($converter->fromSQL(ConverterInterface::TYPE_NULL, "null"));
        $this->assertNull($converter->fromSQL(ConverterInterface::TYPE_NULL, "some string"));
        $this->assertNull($converter->fromSQL(ConverterInterface::TYPE_NULL, 12));
        $this->assertNull($converter->toSQL(ConverterInterface::TYPE_NULL, null));
        $this->assertSame(ConverterInterface::TYPE_NULL, $converter->guessType(null));
    }

    /**
     * Data provider
     */
    public function getIntTypes(): array
    {
        return \array_map(function ($value) { return [$value]; }, self::TYPES_INT);
    }

    /**
     * @dataProvider getIntTypes
     */
    public function testIntConversion($type)
    {
        $converter = new DefaultConverter();
        $this->assertSame("12", $converter->toSQL($type, 12));
        $this->assertSame(12, $converter->fromSQL($type, "12"));
    }

    /**
     * Data provider
     */
    public function getStringTypes(): array
    {
        return \array_map(function ($value) { return [$value]; }, self::TYPES_STRING);
    }

    /**
     * @dataProvider getStringTypes
     */
    public function testStringConversion($type)
    {
        $converter = new DefaultConverter();
        $this->assertSame("Yeah !", $converter->toSQL($type, "Yeah !"));
        $this->assertSame("Booh...", $converter->fromSQL($type, "Booh..."));
    }

    public function testUuidConversion()
    {
        $this->markTestSkipped("Implement me");
    }

    public function testJsonConversion()
    {
        $this->markTestSkipped("Implement me");
    }

    public function testBoolConversion()
    {
        $this->markTestSkipped("Implement me");
    }

    public function testDatetimeConversion()
    {
        $this->markTestSkipped("Implement me");
    }

    public function testDateConversion()
    {
        $this->markTestSkipped("Implement me");
    }

    /**
     * Data provider
     */
    public function getDecimalAndFloatTypes(): array
    {
        return \array_map(function ($value) { return [$value]; }, self::TYPES_NUMERIC);
    }

    /**
     * @dataProvider getDecimalAndFloatTypes
     */
    public function testDecimalAndFloatConversion($type)
    {
        $converter = new DefaultConverter();

        $this->assertSame("12.3456789", $converter->toSQL($type, 12.3456789));
        $value = $converter->fromSQL($type, "12.3456789");
        $this->assertTrue(\is_float($value));
        $this->assertEquals(12.3456789, $value);

        // Integer will go through
        // Integer will be given as float once converted back from SQL
        $this->assertSame("42", $converter->toSQL($type, 42));
        $value = $converter->fromSQL($type, "42");
        $this->assertTrue(\is_float($value));
        $this->assertEquals(42, $value);
    }

    public function testUnknownTypeConversionToString()
    {
        $converter = new DefaultConverter();
        $this->assertSame("I am a string", $converter->toSQL('varchar', new StupidObjectWithToString()));
    }

    public function testBlobConversion()
    {
        $this->markTestSkipped("Implement me");
    }

    public function testBlobWithNulChar()
    {
        $this->markTestSkipped("Implement me");
    }

    public function testRegisterOverride()
    {
        $this->markTestSkipped("Implement me");
    }

    public function testRegisterOverrideNonAllowedFails()
    {
        $this->markTestSkipped("Implement me");
    }

    public function testTypeRegisterWithAliases()
    {
        $this->markTestSkipped("Implement me");
    }

    public function testTypeGuessing()
    {
        $converter = new DefaultConverter();
        $converter->register(new IntervalValueConverter());

        $this->assertSame('bool', $converter->guessType(true));
        $this->assertSame('interval', $converter->guessType(\DateInterval::createFromDateString('1 hour')));
        $this->assertSame('numeric', $converter->guessType(12.34));
        $this->assertSame('timestamp', $converter->guessType(new \DateTime()));
        $this->assertSame('varchar', $converter->guessType('pouet'));
        $this->assertSame('varchar', $converter->guessType(new StupidObjectWithToString()));
    }
}
