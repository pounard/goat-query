<?php

declare(strict_types=1);

namespace Goat\Converter\Tests\Impl;

use Goat\Converter\Impl\DateValueConverter;
use Goat\Converter\Tests\WithConverterTestTrait;
use PHPUnit\Framework\TestCase;

class DateValueConverterTest extends TestCase
{
    use WithConverterTestTrait;

    public function testFromSqlDateTimeWithUsecTz(): void
    {
        // This time zone is GMT+1 on Europe/Paris.
        $sqlDate = '2020-11-27 13:42:34.901965+00';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL('datetime', $sqlDate, $context)->format(DateValueConverter::FORMAT_DATETIME_USEC_TZ),
            '2020-11-27 14:42:34.901965+01:00'
        );
    }

    public function testFromSqlDateTimeWithTz(): void
    {
        // This time zone is GMT+1 on Europe/Paris.
        $sqlDate = '2020-11-27 13:42:34+00';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL('datetime', $sqlDate, $context)->format(DateValueConverter::FORMAT_DATETIME_USEC_TZ),
            '2020-11-27 14:42:34.000000+01:00'
        );
    }

    public function testFromSqlDateTimeWithUsec(): void
    {
        // This time zone is GMT+1 on Europe/Paris.
        // Date will remain the same, since we don't know the original TZ.
        $sqlDate = '2020-11-27 13:42:34.901965';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL('datetime', $sqlDate, $context)->format(DateValueConverter::FORMAT_DATETIME_USEC_TZ),
            '2020-11-27 13:42:34.901965+01:00'
        );
    }

    public function testFromSqlDateTime(): void
    {
        // This time zone is GMT+1 on Europe/Paris.
        // Date will remain the same, since we don't know the original TZ.
        $sqlDate = '2020-11-27 13:42:34';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL('datetime', $sqlDate, $context)->format(DateValueConverter::FORMAT_DATETIME_USEC_TZ),
            '2020-11-27 13:42:34.000000+01:00'
        );
    }

    public function testFromSqlTimeWithUsecTz(): void
    {
        $sqlDate = '13:42:34.901965+00';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL('time', $sqlDate, $context)->format(DateValueConverter::FORMAT_TIME_USEC_TZ),
            '14:42:34.901965+01:00'
        );
    }

    public function testFromSqlTimeWithTz(): void
    {
        $sqlDate = '13:42:34+00';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL('time', $sqlDate, $context)->format(DateValueConverter::FORMAT_TIME_USEC_TZ),
            '14:42:34.000000+01:00'
        );
    }

    public function testFromSqlTimeWithUsec(): void
    {
        $sqlDate = '13:42:34.901965';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL('time', $sqlDate, $context)->format(DateValueConverter::FORMAT_TIME_USEC_TZ),
            '13:42:34.901965+01:00'
        );
    }

    public function testFromSqlTime(): void
    {
        $sqlDate = '13:42:34';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL('time', $sqlDate, $context)->format(DateValueConverter::FORMAT_TIME_USEC_TZ),
            '13:42:34.000000+01:00'
        );
    }

    public function testToSqlDateTimeWithTz(): void
    {
        // Date is given at GMT+3 at the given date.
        $date = new \DateTime('2020-11-27 13:42:34', new \DateTimeZone("Africa/Nairobi"));

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->toSQL('timestamptz', $date, $context),
            '2020-11-27 11:42:34.000000'
        );
    }

    public function testToSqlDateTime(): void
    {
        // Date is given at GMT+3 at the given date.
        $date = new \DateTime('2020-11-27 13:42:34', new \DateTimeZone("Africa/Nairobi"));

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->toSQL('timestamp', $date, $context),
            '2020-11-27 11:42:34.000000'
        );
    }

    public function testToSqlTimeWithTz(): void
    {
        // Date is given at GMT+3 at the given date.
        $date = new \DateTime('2020-11-27 13:42:34', new \DateTimeZone("Africa/Nairobi"));

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->toSQL('timez', $date, $context),
            '11:42:34.000000'
        );
    }

    public function testToSqlTime(): void
    {
        // Date is given at GMT+3 at the given date.
        $date = new \DateTime('2020-11-27 13:42:34', new \DateTimeZone("Africa/Nairobi"));

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->toSQL('time', $date, $context),
            '11:42:34.000000'
        );
    }
}
