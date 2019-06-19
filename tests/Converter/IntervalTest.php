<?php

declare(strict_types=1);

namespace Goat\Converter\Tests;

use Goat\Converter\DefaultConverter;
use Goat\Converter\TypeConversionError;
use Goat\Converter\Impl\IntervalValueConverter;
use PHPUnit\Framework\TestCase;

class IntervalTest extends TestCase
{
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
    public function testFromSQL(string $value, string $expected)
    {
        $defaultConverter = new DefaultConverter();
        $converter = new IntervalValueConverter();

        $extracted = $converter->fromSQL('interval', $value, $defaultConverter);
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
    public function testValidToSQL($expected, $value)
    {
        $defaultConverter = new DefaultConverter();
        $converter = new IntervalValueConverter();

        // Converter only supports PHP \DateInterval structures as input
        $this->assertSame($expected, $converter->toSQL('interval', $value, $defaultConverter));
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
    public function testInvalidToSQL($invalidValue)
    {
        $defaultConverter = new DefaultConverter();
        $converter = new IntervalValueConverter();

        $this->expectException(TypeConversionError::class);
        $converter->toSQL('interval', $invalidValue, $defaultConverter);
    }
}
