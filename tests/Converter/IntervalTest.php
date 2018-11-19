<?php

declare(strict_types=1);

namespace Goat\Converter\Tests;

use Goat\Converter\TypeConversionError;
use Goat\Converter\Impl\IntervalValueConverter;
use PHPUnit\Framework\TestCase;

class IntervalTest extends TestCase
{
    public function testFromSQL()
    {
        $converter = new IntervalValueConverter();

        $valid = [
            '1 year 2 days 00:01:00'    => 'P1Y2DT1M',
            'P1Y2DT1M'                  => 'P1Y2DT1M',
            '2 hour 1 minute 30 second' => 'PT2H1M30S',
            '02:01:30'                  => 'PT2H1M30S',
            'PT2H1M30S'                 => 'PT2H1M30S',
        ];

        foreach ($valid as $value => $expected) {
            $extracted = $converter->fromSQL('interval', $value);
            $this->assertInstanceOf(\DateInterval::class, $extracted);
            $this->assertSame($expected, IntervalValueConverter::formatIntervalAsISO8601($extracted));
        }
    }

    public function testToSQL()
    {
        $converter = new IntervalValueConverter();

        // Converter only supports PHP \DateInterval structures as input
        $valid = [
            'P1Y2DT1M'  => new \DateInterval('P1Y2DT1M'),
            'PT2H1M30S' => new \DateInterval('PT2H1M30S'),
        ];

        foreach ($valid as $expected => $value) {
            $this->assertSame($expected, $converter->toSQL('interval', $value));
        }

        // Stupid values
        $invalid = ['aahhh', 12, new \DateTime(), [], 'P1Y2DT1M', null];

        foreach ($invalid as $value) {
            try {
                $converter->toSQL('interval', $value);
                $this->fail();
            } catch (TypeConversionError $e) {
                // Okay!
            }
        }
    }
}
