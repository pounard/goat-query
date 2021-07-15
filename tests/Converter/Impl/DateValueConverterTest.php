<?php

declare(strict_types=1);

namespace Goat\Converter\Tests\Impl;

use Goat\Converter\Impl\DateValueConverter;
use Goat\Converter\Tests\WithConverterTestTrait;
use PHPUnit\Framework\TestCase;

class DateValueConverterTest extends TestCase
{
    use WithConverterTestTrait;

    public function dataPhpTypeAndSqlDateType()
    {
        foreach ([\DateTime::class, \DateTimeImmutable::class, \DateTimeInterface::class] as $phpType) {
            foreach (['timestamp', 'timestamp with time zone'] as $sqlType) {
                yield [$phpType, $sqlType];
            }
        }
    }

    /**
     * @dataProvider dataPhpTypeAndSqlDateType()
     */
    public function testFromSqlDateTimeWithUsecTz(string $phpType, string $sqlType): void
    {
        // This time zone is GMT+1 on Europe/Paris.
        $sqlDate = '2020-11-27 13:42:34.901965+00';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL($phpType, $sqlType, $sqlDate, $context)->format(DateValueConverter::FORMAT_DATETIME_USEC_TZ),
            '2020-11-27 14:42:34.901965+01:00'
        );
    }

    /**
     * @dataProvider dataPhpTypeAndSqlDateType()
     */
    public function testFromSqlDateTimeWithTz(string $phpType, string $sqlType): void
    {
        // This time zone is GMT+1 on Europe/Paris.
        $sqlDate = '2020-11-27 13:42:34+00';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL($phpType, $sqlType, $sqlDate, $context)->format(DateValueConverter::FORMAT_DATETIME_USEC_TZ),
            '2020-11-27 14:42:34.000000+01:00'
        );
    }

    /**
     * @dataProvider dataPhpTypeAndSqlDateType()
     */
    public function testFromSqlDateTimeWithUsec(string $phpType, string $sqlType): void
    {
        // This time zone is GMT+1 on Europe/Paris.
        // Date will remain the same, since we don't know the original TZ.
        $sqlDate = '2020-11-27 13:42:34.901965';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL($phpType, $sqlType, $sqlDate, $context)->format(DateValueConverter::FORMAT_DATETIME_USEC_TZ),
            '2020-11-27 13:42:34.901965+01:00'
        );
    }

    /**
     * @dataProvider dataPhpTypeAndSqlDateType()
     */
    public function testFromSqlDateTime(string $phpType, string $sqlType): void
    {
        // This time zone is GMT+1 on Europe/Paris.
        // Date will remain the same, since we don't know the original TZ.
        $sqlDate = '2020-11-27 13:42:34';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL($phpType, $sqlType, $sqlDate, $context)->format(DateValueConverter::FORMAT_DATETIME_USEC_TZ),
            '2020-11-27 13:42:34.000000+01:00'
        );
    }

    public function dataPhpTypeAndSqlTimeType()
    {
        foreach ([\DateTime::class, \DateTimeImmutable::class, \DateTimeInterface::class] as $phpType) {
            foreach (['time', 'time with time zone'] as $sqlType) {
                yield [$phpType, $sqlType];
            }
        }
    }

    /**
     * @dataProvider dataPhpTypeAndSqlTimeType()
     */
    public function testFromSqlTimeWithUsecTz(string $phpType, string $sqlType): void
    {
        self::markTestIncomplete("Time with time zone causes erreneous result depending on the local time zone, code needs to be fixed.");

        $sqlDate = '13:42:34.901965+00';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL($phpType, $sqlType, $sqlDate, $context)->format(DateValueConverter::FORMAT_TIME_USEC_TZ),
            '14:42:34.901965+01:00'
        );
    }

    /**
     * @dataProvider dataPhpTypeAndSqlTimeType()
     */
    public function testFromSqlTimeWithTz(string $phpType, string $sqlType): void
    {
        self::markTestIncomplete("Time with time zone causes erreneous result depending on the local time zone, code needs to be fixed.");

        $sqlDate = '13:42:34+00';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL($phpType, $sqlType, $sqlDate, $context)->format(DateValueConverter::FORMAT_TIME_USEC_TZ),
            '14:42:34.000000+01:00'
        );
    }

    /**
     * @dataProvider dataPhpTypeAndSqlTimeType()
     */
    public function testFromSqlTimeWithUsec(string $phpType, string $sqlType): void
    {
        self::markTestIncomplete("Time with time zone causes erreneous result depending on the local time zone, code needs to be fixed.");

        $sqlDate = '13:42:34.901965';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL($phpType, $sqlType, $sqlDate, $context)->format(DateValueConverter::FORMAT_TIME_USEC_TZ),
            '13:42:34.901965+01:00'
        );
    }

    /**
     * @dataProvider dataPhpTypeAndSqlTimeType()
     */
    public function testFromSqlTime(string $phpType, string $sqlType): void
    {
        self::markTestIncomplete("Time with time zone causes erreneous result depending on the local time zone, code needs to be fixed.");

        $sqlDate = '13:42:34';

        $context = self::contextWithTimeZone('Europe/Paris');
        $instance = new DateValueConverter();

        self::assertSame(
            $instance->fromSQL($phpType, $sqlType, $sqlDate, $context)->format(DateValueConverter::FORMAT_TIME_USEC_TZ),
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
            $instance->toSQL('timestamp with time zone', $date, $context),
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
            $instance->toSQL('time with time zone', $date, $context),
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
