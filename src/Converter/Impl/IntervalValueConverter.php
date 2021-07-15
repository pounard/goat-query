<?php

declare(strict_types=1);

namespace Goat\Converter\Impl;

use Goat\Converter\ConverterContext;
use Goat\Converter\StaticInputValueConverter;
use Goat\Converter\StaticOutputValueConverter;
use Goat\Converter\TypeConversionError;

class IntervalValueConverter implements StaticInputValueConverter, StaticOutputValueConverter
{
    /**
     * Format interval as an ISO8601 string.
     */
    public static function formatIntervalAsISO8601(\DateInterval $interval) : string
    {
        // All credits to https://stackoverflow.com/a/33787489
        $string = $interval->format("P%yY%mM%dDT%hH%iM%sS");

        // I would prefer a single \str_replace() \strtr() call but it seems that
        // PHP does not guarante order in replacements, and I have different
        // behaviours depending upon versions.
        $replacements = [
            "M0S" => "M",
            "H0M" => "H",
            "T0H" => "T",
            "D0H" => "D",
            "M0D" => "M",
            "Y0M" => "Y",
            "P0Y" => "P",
        ];
        foreach ($replacements as $search => $replace) {
            $string = \str_replace($search, $replace, $string);
        }

        return $string;
    }

    /**
     * Convert PostgreSQL formatted string to \DateInterval.
     */
    public static function extractPostgreSQLAsInterval(string $value) : \DateInterval
    {
        if ('P' === $value[0] && !\strpos(' ', $value)) {
            return new \DateInterval($value);
        }

        $pos = null;
        if (false === ($pos = strrpos($value, ' '))) {
            // Got ourselves a nice "01:23:56" string
            list($hour, $min, $sec) = \explode(':', $value);
            return \DateInterval::createFromDateString(\sprintf("%d hour %d min %d sec", $hour, $min, $sec));
        } else if (false === \strpos($value, ':')) {
            // Got ourselves a nice "1 year ..." string
            return \DateInterval::createFromDateString(\strtr($value, [
                'mons' => 'months',
                'mon' => 'month',
            ]));
        } else {
            // Mixed PostgreSQL format "1 year... HH:MM:SS"
            $date = \mb_substr($value, 0, $pos);
            $time = \mb_substr($value, $pos + 1);
            list($hour, $min, $sec) = \explode(':', $time);
            return \DateInterval::createFromDateString(\sprintf("%s %d hour %d min %d sec", $date, $hour, $min, $sec));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportedOutputTypes(): array
    {
        return [
            'interval' => [\DateInterval::class],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $phpType, ?string $sqlType, string $value, ConverterContext $context)
    {
        return self::extractPostgreSQLAsInterval($value);
    }

    /**
     * {@inheritdoc}
     */
    public function supportedInputTypes(): array
    {
        return [
            \DateInterval::class => ['interval'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value, ConverterContext $context): ?string
    {
        if (!$value instanceof \DateInterval) {
            throw new TypeConversionError(\sprintf("Value must be an instance of %s", \DateInterval::class));
        }

        return self::formatIntervalAsISO8601($value);
    }
}
