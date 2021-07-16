<?php

declare(strict_types=1);

namespace Goat\Converter\Tests;

use PHPUnit\Framework\TestCase;

class LegacyDefaultConverterTest extends TestCase
{
    const TYPES_INT = [
        'bigserial', 'serial', 'serial2', 'serial4', 'serial8', 'smallserial',
        'bigint', 'int', 'int2', 'int4', 'int8', 'integer', 'smallint',
    ];

    const TYPES_STRING = [
        'char', 'character', 'text', 'varchar'
    ];

    const TYPES_NUMERIC = [
        'decimal', 'double', 'float4', 'float8', 'numeric', 'real'
    ];

    use WithConverterTestTrait;

    public function testNullConversion(): void
    {
        $converter = self::defaultConverter();
        $context = self::context($converter);

        self::assertNull($converter->fromSQL(null, null, null, $context));
        self::assertNull($converter->fromSQL(null, "some string", null, $context));
        self::assertNull($converter->fromSQL(null, null, "some string", $context));
        self::assertNull($converter->fromSQL('foo', 'null', null, $context));
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

        self::assertSame("12", $converter->toSQL(12, $type, $context));
        self::assertSame(12, $converter->fromSQL("12", $type, 'int', $context));
        self::assertSame(12, $converter->fromSQL("12", $type, null, $context));
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

        self::assertSame("Yeah !", $converter->toSQL("Yeah !", $type, $context));
        self::assertSame("Booh...", $converter->fromSQL("Booh...", $type, null, $context));
        self::assertSame("Booh...", $converter->fromSQL("Booh...", $type, 'string', $context));
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

        self::assertSame("12.3456789", $converter->toSQL(12.3456789, $type, $context));
        $value = $converter->fromSQL("12.3456789", $type, null, $context);
        self::assertTrue(\is_float($value));
        self::assertEquals(12.3456789, $value);
        $value = $converter->fromSQL("12.3456789", $type, 'float', $context);
        self::assertTrue(\is_float($value));
        self::assertEquals(12.3456789, $value);

        // Integer will go through
        // Integer will be given as float once converted back from SQL
        self::assertSame("42", $converter->toSQL(42, $type, $context));
        $value = $converter->fromSQL("42", $type, null, $context);
        self::assertTrue(\is_float($value));
        self::assertEquals(42, $value);
        $value = $converter->fromSQL("42", $type, 'float', $context);
        self::assertTrue(\is_float($value));
        self::assertEquals(42, $value);
    }
}
