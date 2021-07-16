<?php

declare(strict_types=1);

namespace Goat\Converter\Impl;

use Goat\Converter\ConverterContext;
use Goat\Converter\StaticInputValueConverter;
use Goat\Converter\StaticOutputValueConverter;
use Goat\Converter\TypeConversionError;

/**
 * This will fit with most RDBMS since that:
 *
 *   - MySQL will truncate date strings (so it ignores micro seconds),
 *   - MSSQL will handle micro seconds,
 *   - PostgreSQL handles much more.
 *
 * There a slight PostgreSQL only variant in reading output dates, which is
 * the attempt find time zone offset in SQL dates. This variation happens
 * when reading date from SQL only, so it cannot actually gives erroneous
 * values to the RDMBS and will not cause SQL syntax errors with servers that
 * don't support this.
 *
 * When inserting data, it considers that the RDBMS connection has been set up
 * with the same user configured time zone than we have in memory, so that the
 * database server will proceed to conversions by itself if necessary. This is
 * actually the case with PostgreSQL. In that regard, we only convert time
 * zones when the input \DateTimeInterface has not the same time zone as the
 * user configured time zone in order to give the server the correct date.
 *
 * @see https://www.postgresql.org/docs/13/datatype-datetime.html
 */
class DateValueConverter implements StaticInputValueConverter, StaticOutputValueConverter
{
    const FORMAT_DATE = 'Y-m-d';
    const FORMAT_DATETIME = 'Y-m-d H:i:s';
    const FORMAT_DATETIME_TZ = 'Y-m-d H:i:sP';
    const FORMAT_DATETIME_USEC = 'Y-m-d H:i:s.u';
    const FORMAT_DATETIME_USEC_TZ = 'Y-m-d H:i:s.uP';
    const FORMAT_TIME = 'H:i:s';
    const FORMAT_TIME_TZ = 'H:i:sP';
    const FORMAT_TIME_USEC = 'H:i:s.u';
    const FORMAT_TIME_USEC_TZ = 'H:i:s.uP';

    /**
     * {@inheritdoc}
     */
    public function supportedOutputTypes(): array
    {
        return [
            'timestamp' => [
                \DateTime::class,
                \DateTimeImmutable::class,
                \DateTimeInterface::class,
            ],
            'timestamp with time zone' => [
                \DateTime::class,
                \DateTimeImmutable::class,
                \DateTimeInterface::class,
            ],
            'date' => [
                \DateTime::class,
                \DateTimeImmutable::class,
                \DateTimeInterface::class,
            ],
            'time' => [
                \DateTime::class,
                \DateTimeImmutable::class,
                \DateTimeInterface::class,
            ],
            'time with time zone' => [
                \DateTime::class,
                \DateTimeImmutable::class,
                \DateTimeInterface::class,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $phpType, ?string $sqlType, string $value, ConverterContext $context)
    {
        $ret = $this->doFromSQL($phpType, $sqlType ?? 'timestamp', $value, $context);

        if (\DateTime::class === $phpType) {
            return \DateTime::createFromImmutable($ret);
        }

        return $ret;
    }

    /**
     * Right now, the code is rock solid, but it might be slow.
     *
     * This needs benchmarking, and might be some other solutions could be
     * explored. I believe that \DateTimeImmutable::createFromFormat() to be
     * fast enough; I might be wrong.
     */
    private function doFromSQL(string $phpType, string $sqlType, string $value, ConverterContext $context): \DateTimeImmutable
    {
        // I have no idea why this is still here. Probably an old bug.
        if (!$value = \trim($value)) {
            return null;
        }

        $doConvert = false;

        switch ($sqlType) {

            case 'date':
                if ($ret = \DateTimeImmutable::createFromFormat(self::FORMAT_DATE, $value)) {
                    // Date only do not care about time zone.
                } else {
                    throw new TypeConversionError(\sprintf("Given date '%s' could not be parsed.", $value));
                }
                return $ret;

            case 'timestamp':
            case 'timestamp with time zone':
                $userTimeZone = new \DateTimeZone($context->getClientTimeZone());

                // Attempt all possible outcomes.
                if ($ret = \DateTimeImmutable::createFromFormat(self::FORMAT_DATETIME_USEC_TZ, $value)) {
                    // Time zone is within the date, as an offset. Convert the
                    // date to the user configured time zone, this conversion
                    // is safe and time will not shift.
                    $doConvert = true;
                } else if ($ret = \DateTimeImmutable::createFromFormat(self::FORMAT_DATETIME_USEC, $value, $userTimeZone)) {
                    // We have no offset, change object timezone to be the user
                    // configured one if different from PHP default one. This
                    // will cause possible time shifts if client that inserted
                    // this date did not have the same timezone configured.
                    $doConvert = false;
                } else if ($ret = \DateTimeImmutable::createFromFormat(self::FORMAT_DATETIME_TZ, $value)) {
                    // Once again, we have an offset. See upper.
                    $doConvert = true;
                } else if ($ret = \DateTimeImmutable::createFromFormat(self::FORMAT_DATETIME, $value, $userTimeZone)) {
                    // Once again, no offset. See upper.
                    $doConvert = false;
                } else {
                    throw new TypeConversionError(\sprintf("Given datetime '%s' could not be parsed.", $value));
                }

                if ($doConvert && $ret->getTimezone()->getName() !== $userTimeZone->getName()) {
                    return $ret->setTimezone($userTimeZone);
                }
                return $ret;

            case 'time':
            case 'time with time zone':
                $userTimeZone = new \DateTimeZone($context->getClientTimeZone());

                // Attempt all possible outcomes.
                if ($ret = \DateTimeImmutable::createFromFormat(self::FORMAT_TIME_USEC_TZ, $value)) {
                    // Time zone is within the date, as an offset. Convert the
                    // date to the user configured time zone, this conversion
                    // is safe and time will not shift.
                    $doConvert = true;
                } else if ($ret = \DateTimeImmutable::createFromFormat(self::FORMAT_TIME_USEC, $value, $userTimeZone)) {
                    // We have no offset, change object timezone to be the user
                    // configured one if different from PHP default one. This
                    // will cause possible time shifts if client that inserted
                    // this date did not have the same timezone configured.
                    $doConvert = false;
                } else if ($ret = \DateTimeImmutable::createFromFormat(self::FORMAT_TIME_TZ, $value)) {
                    // Once again, we have an offset. See upper.
                    $doConvert = true;
                } else if ($ret = \DateTimeImmutable::createFromFormat(self::FORMAT_TIME, $value, $userTimeZone)) {
                    // Once again, no offset. See upper.
                    $doConvert = false;
                } else {
                    throw new TypeConversionError(\sprintf("Given time '%s' could not be parsed.", $value));
                }

                if ($doConvert && $ret->getTimezone()->getName() !== $userTimeZone->getName()) {
                    return $ret->setTimezone($userTimeZone);
                }
                return $ret;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportedInputTypes(): array
    {
        return [
            \DateTime::class =>  [
                'timestamp',
                'timestamp with time zone',
                'date',
                'time',
                'time with time zone',
            ],
            \DateTimeImmutable::class =>  [
                'timestamp',
                'timestamp with time zone',
                'date',
                'time',
                'time with time zone',
            ],
            \DateTimeInterface::class =>  [
                'timestamp',
                'timestamp with time zone',
                'date',
                'time',
                'time with time zone',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value, ConverterContext $context): ?string
    {
        if (!$value instanceof \DateTimeInterface) {
            throw new TypeConversionError(\sprintf("Given value '%s' is not instanceof \DateTimeInterface.", $value));
        }

        switch ($type) {

            case 'date':
                return $value->format(self::FORMAT_DATE);

            case 'timestamp':
            case 'timestamp with time zone':
                $userTimeZone = new \DateTimeZone($context->getClientTimeZone());
                // If user given date time is not using the client timezone
                // enfore conversion on the PHP side, since the SQL backend
                // does not care about the time zone at this point and will
                // not accept it.
                if ($value->getTimezone()->getName() !== $userTimeZone->getName()) {
                    if (!$value instanceof \DateTimeImmutable) {
                        // Avoid side-effect in user data.
                        $value = clone $value;
                    }
                    $value = $value->setTimezone($userTimeZone);
                }
                return $value->format(self::FORMAT_DATETIME_USEC);

            case 'time':
            case 'time with time zone':
                $userTimeZone = new \DateTimeZone($context->getClientTimeZone());
                // If user given date time is not using the client timezone
                // enfore conversion on the PHP side, since the SQL backend
                // does not care about the time zone at this point and will
                // not accept it.
                if ($value->getTimezone()->getName() !== $userTimeZone->getName()) {
                    if (!$value instanceof \DateTimeImmutable) {
                        // Avoid side-effect in user data.
                        $value = clone $value;
                    }
                    $value = $value->setTimezone($userTimeZone);
                }
                return $value->format(self::FORMAT_TIME_USEC);

            default:
                throw new TypeConversionError(\sprintf("Given value '%s' is not instanceof \DateTimeInterface.", $value));
        }
    }
}
