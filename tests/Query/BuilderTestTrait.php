<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Driver\Platform\Query\MySQL8Writer;
use Goat\Driver\Platform\Query\MySQLWriter;
use Goat\Driver\Platform\Query\PgSQLWriter;
use Goat\Driver\Query\DefaultSqlWriter;
use Goat\Driver\Query\FormattedQuery;
use Goat\Driver\Query\SqlWriter;
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
                self::normalize((string) $expected),
                self::normalize((string) $actual),
                $message
            );
        }
        return self::assertSame(
            self::normalize((string) $expected),
            self::normalize((string) $actual)
        );
    }

    protected static function createMySQL8Writer(): SqlWriter
    {
        return new MySQL8Writer(new NullEscaper());
    }

    protected static function createMySQLWriter(): SqlWriter
    {
        return new MySQLWriter(new NullEscaper());
    }

    protected static function createPgSQLWriter(): SqlWriter
    {
        return new PgSQLWriter(new NullEscaper());
    }

    protected static function createStandardSqlWriter(): SqlWriter
    {
        return new DefaultSqlWriter(new NullEscaper());
    }

    protected static function prepare($query): FormattedQuery
    {
        return self::createStandardSqlWriter()->prepare($query);
    }

    protected static function format($query): string
    {
        return self::prepare($query)->toString();
    }
}
