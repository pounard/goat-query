<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\Writer\DefaultFormatter;
use Goat\Runner\Testing\NullEscaper;

trait BuilderTestTrait
{
    private static function normalize($string)
    {
        $string = \preg_replace('@\s*(\(|\))\s*@ms', '$1', $string);
        $string = \preg_replace('@\s*,\s*@ms', ',', $string);
        $string = \preg_replace('@\s+@ms', ' ', $string);
        $string = \strtolower($string);
        $string = \trim($string);

        return $string;
    }

    protected static function assertSameSql($expected, $actual, $message = null)
    {
        if ($message) {
            return self::assertSame(
                self::normalize($expected),
                self::normalize($actual),
                $message
            );
        }
        return self::assertSame(
            self::normalize($expected),
            self::normalize($actual)
        );
    }

    protected static function createStandardFormatter()
    {
        return new DefaultFormatter(new NullEscaper());
    }
}
