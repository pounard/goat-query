<?php

declare(strict_types=1);

namespace Goat\Converter\Tests;

use Goat\Converter\ConverterInterface;
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

    use WithConverterTestTrait;

    public function testNullConversion(): void
    {
        $converter = self::defaultConverter();
        $context = self::context($converter);

        self::assertNull($converter->fromSQL(ConverterInterface::TYPE_NULL, "null", $context));
        self::assertNull($converter->fromSQL(ConverterInterface::TYPE_NULL, "some string", $context));
        self::assertNull($converter->fromSQL(ConverterInterface::TYPE_NULL, 12, $context));
        self::assertNull($converter->toSQL(ConverterInterface::TYPE_NULL, null, $context));
        self::assertSame(ConverterInterface::TYPE_NULL, $converter->guessType(null, $context));
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
    public function testIntConversion($type): void
    {
        $converter = self::defaultConverter();
        $context = self::context($converter);

        self::assertSame("12", $converter->toSQL($type, 12, $context));
        self::assertSame(12, $converter->fromSQL($type, "12", $context));
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
    public function testStringConversion($type): void
    {
        $converter = self::defaultConverter();
        $context = self::context($converter);

        self::assertSame("Yeah !", $converter->toSQL($type, "Yeah !", $context));
        self::assertSame("Booh...", $converter->fromSQL($type, "Booh...", $context));
    }

    public function testUuidConversion(): void
    {
        self::markTestSkipped("Implement me");
    }

    public function testJsonConversion(): void
    {
        self::markTestSkipped("Implement me");
    }

    public function testBoolConversion(): void
    {
        self::markTestSkipped("Implement me");
    }

    public function testDatetimeConversion(): void
    {
        self::markTestSkipped("Implement me");
    }

    public function testDateConversion(): void
    {
        self::markTestSkipped("Implement me");
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
    public function testDecimalAndFloatConversion($type): void
    {
        $converter = self::defaultConverter();
        $context = self::context($converter);

        self::assertSame("12.3456789", $converter->toSQL($type, 12.3456789, $context));
        $value = $converter->fromSQL($type, "12.3456789", $context);
        self::assertTrue(\is_float($value));
        self::assertEquals(12.3456789, $value);

        // Integer will go through
        // Integer will be given as float once converted back from SQL
        self::assertSame("42", $converter->toSQL($type, 42, $context));
        $value = $converter->fromSQL($type, "42", $context);
        self::assertTrue(\is_float($value));
        self::assertEquals(42, $value);
    }

    public function testUnknownTypeConversionToString(): void
    {
        $converter = self::defaultConverter();
        $context = self::context($converter);

        self::assertSame("I am a string", $converter->toSQL('varchar', new StupidObjectWithToString(), $context));
    }

    public function testBlobConversion(): void
    {
        self::markTestSkipped("Implement me");
    }

    public function testBlobWithNulChar(): void
    {
        self::markTestSkipped("Implement me");
    }

    public function testRegisterOverride(): void
    {
        self::markTestSkipped("Implement me");
    }

    public function testRegisterOverrideNonAllowedFails(): void
    {
        self::markTestSkipped("Implement me");
    }

    public function testTypeRegisterWithAliases(): void
    {
        self::markTestSkipped("Implement me");
    }

    public function testTypeGuessing(): void
    {
        $converter = self::defaultConverter();
        $context = self::context($converter);

        self::assertSame('bool', $converter->guessType(true, $context));
        self::assertSame('interval', $converter->guessType(\DateInterval::createFromDateString('1 hour'), $context));
        self::assertSame('numeric', $converter->guessType(12.34, $context));
        self::assertSame('timestamptz', $converter->guessType(new \DateTime(), $context));
        self::assertSame('varchar', $converter->guessType('pouet', $context));
        self::assertSame('varchar', $converter->guessType(new StupidObjectWithToString(), $context));
    }
}
