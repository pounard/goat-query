<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Driver\Platform\Query\PgSQLWriter;
use Goat\Driver\Query\DefaultSqlWriter;
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

    protected static function createPgSQLWriter()
    {
        return new PgSQLWriter(new NullEscaper());
    }

    protected static function createStandardSqlWriter()
    {
        return new DefaultSqlWriter(new NullEscaper());
    }
}
