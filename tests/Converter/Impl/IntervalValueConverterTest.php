<?php

declare(strict_types=1);

namespace Goat\Converter\Tests\Impl;

use Goat\Converter\TypeConversionError;
use Goat\Converter\Impl\IntervalValueConverter;
use Goat\Converter\Tests\WithConverterTestTrait;
use PHPUnit\Framework\TestCase;

final class IntervalValueConverterTest extends TestCase
{
    use WithConverterTestTrait;

    /**
     * Data provider
     */
    public function getValidFromSQLData()
    {
        return [
            ['1 year 2 days 00:01:00', 'P1Y2DT1M'],
            ['P1Y2DT1M', 'P1Y2DT1M'],
            ['2 hour 1 minute 30 second', 'PT2H1M30S'],
            ['02:01:30', 'PT2H1M30S'],
            ['PT2H1M30S', 'PT2H1M30S'],
        ];
    }

    /**
     * @dataProvider getValidFromSQLData
     */
    public function testFromSQL(string $value, string $expected): void
    {
        $converter = new IntervalValueConverter();
        $context = self::context();

        $extracted = $converter->fromSQL('interval', $value, $context);
        $this->assertInstanceOf(\DateInterval::class, $extracted);
        $this->assertSame($expected, IntervalValueConverter::formatIntervalAsISO8601($extracted));
    }

    /**
     * Data provider
     */
    public function getValidToSQLData()
    {
        return [
            ['P1Y2DT1M', new \DateInterval('P1Y2DT1M')],
            ['PT2H1M30S', new \DateInterval('PT2H1M30S')],
        ];
    }

    /**
     * @dataProvider getValidToSQLData
     */
    public function testValidToSQL($expected, $value): void
    {
        $converter = new IntervalValueConverter();
        $context = self::context();

        // Converter only supports PHP \DateInterval structures as input
        $this->assertSame($expected, $converter->toSQL('interval', $value, $context));
    }

    /**
     * Data provider
     */
    public function getInvalidToSQLData()
    {
        return [
            ['aahhh'],
            [12],
            [new \DateTime()],
            [[]],
            ['P1Y2DT1M'],
            [null],
        ];
    }

    /**
     * @dataProvider getInvalidToSQLData
     */
    public function testInvalidToSQL($invalidValue): void
    {
        $converter = new IntervalValueConverter();
        $context = self::context();

        $this->expectException(TypeConversionError::class);
        $converter->toSQL('interval', $invalidValue, $context);
    }
}
