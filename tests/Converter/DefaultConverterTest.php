<?php

declare(strict_types=1);

namespace Goat\Converter\Tests;

use Goat\Converter\TypeConversionError;
use PHPUnit\Framework\TestCase;

final class DefaultConverterTest extends TestCase
{
    use WithConverterTestTrait;

    /**
     * Tests null shortcut.
     */
    public function testToSqlWhenNullValueReturnsNull(): void
    {
        $converter = self::defaultConverter();

        self::assertSame(null, $converter->toSQL(null, 'varchar'));
    }

    /**
     * Tests null shortcut.
     */
    public function testToSqlWhenNullTypeReturnsNull(): void
    {
        $converter = self::defaultConverter();

        self::assertSame(null, $converter->toSQL('foo', 'null'));
    }

    /**
     * Resources are not allowed.
     */
    public function testToSqlWhenResourceThenFail(): void
    {
        try {
            $handle = \fopen(__FILE__, 'r');
            $converter = self::defaultConverter();

            self::expectException(TypeConversionError::class);
            self::expectExceptionMessageMatches('/Resources types are not supported yet/');
            $converter->toSQL($handle, null);
        } finally {
            \fclose($handle);
        }
    }

    /**
     * When an object implements a supported interface of a converter, it
     * will match.
     */
    public function testToSqlWhenObjectImplementsInterface(): void
    {
        self::markTestIncomplete("Not implementeed yet.");
    }

    /**
     * When an object is a sublass of a supported PHP type of a converter,
     * it will match.
     */
    public function testToSqlWhenObjectIsChildClass(): void
    {
        self::markTestIncomplete("Not implemented yet.");
    }

    /**
     * Tests unknown SQL type error case.
     */
    public function testToSqlWhenUnexistingSqlTypeFails(): void
    {
        $converter = self::defaultConverter();

        self::expectException(TypeConversionError::class);
        $converter->toSQL('foo', 'this is a non existing type');
    }

    /**
     * Tests unsupported PHP type error case.
     */
    public function testToSqlWhenUnexistingPhpTypeFails(): void
    {
        $converter = self::defaultConverter();

        self::expectException(TypeConversionError::class);
        $converter->toSQL(new \SplObjectStorage(), null);
    }

    /**
     * Tests alias unwinding works.
     */
    public function testToSqlWhenAlias(): void
    {
        $converter = self::defaultConverter();

        self::assertSame('12', $converter->toSQL(12, 'int4'));
    }

    /**
     * Tests null shortcut.
     */
    public function testFromSqlWhenNullValueReturnsNull(): void
    {
        $converter = self::defaultConverter();

        self::assertNull($converter->fromSQL(null, 'varchar', null));
    }

    /**
     * Tests null shortcut.
     */
    public function testFromSqlWhenNullSqlTypeReturnsNull(): void
    {
        $converter = self::defaultConverter();

        self::assertNull($converter->fromSQL('bar', 'null', 'string'));
    }

    /**
     * Tests when int or float is given instead of string shortcut.
     */
    public function testFromSqlWhenIntReturnInt(): void
    {
        $converter = self::defaultConverter();

        self::assertSame(7, $converter->fromSQL(7, null, null));
    }

    /**
     * Tests when int or float is given instead of string shortcut.
     */
    public function testFromSqlWhenFloatReturnFloat(): void
    {
        $converter = self::defaultConverter();

        self::assertSame(7.2, $converter->fromSQL(7.2, null, null));
    }

    /**
     * Tests when int or float is given instead of string shortcut.
     */
    public function testFromSqlWhenFloatAndIntTypeReturnInt(): void
    {
        $converter = self::defaultConverter();

        self::assertSame(7, $converter->fromSQL(7.0, null, 'int'));
    }

    /**
     * Tests when int or float is given instead of string shortcut.
     */
    public function testFromSqlWhenIntAndFloatTypeReturnFloat(): void
    {
        $converter = self::defaultConverter();

        self::assertSame(7.0, $converter->fromSQL(7, null, 'float'));
    }

    /**
     * Tests that if a converter is registered for a specific interface, an
     * object implementing it will be restitued.
     */
    public function testFromSqlWhenPhpTypeIsInterface(): void
    {
        self::markTestIncomplete("Not implemented yet.");
    }

    /**
     * Tests that if a PHP type which is a parent class of a converter supported
     * PHP type, then the converter will match.
     */
    public function testFromSqlWhenPhpTypeIsParentClass(): void
    {
        self::markTestIncomplete("Not implemented yet.");
    }

    /**
     * When no PHP type is given, a multiple converters do match, then the first
     * one not raising an error will return its result.
     */
    public function testFromSqlWhenNoPhpTypeThenAllMatchingWithSqlAreTested(): void
    {
        self::markTestIncomplete();
    }

    /**
     * Tests unknown SQL type error case.
     */
    public function testFromSqlWhenUnexistingSqlTypeFails(): void
    {
        $converter = self::defaultConverter();

        self::expectException(TypeConversionError::class);
        $converter->fromSQL('foo', 'this is a non existing type', null);
    }

    /**
     * Tests unsupported PHP type error case.
     */
    public function testFromSqlWhenUnexistingPhpTypeFails(): void
    {
        $converter = self::defaultConverter();

        self::expectException(TypeConversionError::class);
        $converter->fromSQL('foo', 'varchar', 'this is a non existing type');
    }

    /**
     * Tests alias unwinding works.
     */
    public function testFromSqlWhenAlias(): void
    {
        $converter = self::defaultConverter();

        self::assertSame(12, $converter->fromSQL('12', 'int4', 'int'));
    }

    /**
     * When the same transition is registered more than once, the last one wins.
     */
    public function testRegisterOrderIsRespected(): void
    {
        self::markTestIncomplete("This is not implemented yet.");
    }
}
