<?php

declare(strict_types=1);

namespace Goat\Converter\Driver;

use Goat\Converter\TypeConversionError;

/**
 * In PgSQL, array are types, you can't have an array with different types within.
 *
 * For now, there are important considerations you should be aware of:
 *   - it will not work recursively, it only handles one dimension,
 *   - it won't convert bothways, only from SQL to PHP.
 *
 * This is very sensitive code, any typo will breaks tons of stuff, please
 * always run unit tests, everytime you modify anything in here, really.
 */
final class PgSQLParser
{
    /**
     * Parse a pgsql array return string
     *
     * @todo support more than one dimension
     */
    public static function parseArray(string $string): array
    {
        $length = \mb_strlen($string);

        if (0 === $length) { // Empty string
            return [];
        }
        if ($length < 2) {
            throw new \Exception("malformed input: string length must be 0 or at least 2");
        }
        if ('{' !== $string[0] || '}' !== $string[$length - 1]) {
            throw new \Exception("malformed input: array must be enclosed using {}");
        }

        return self::parseArrayRecursion($string, 1, $length)[0];
    }

    /**
     * Write array
     */
    public static function writeArray(array $data, callable $serializeItem): string
    {
        $values = [];

        foreach ($data as $value) {
            if (\is_array($value)) {
                $values[] = self::writeArray($value, $serializeItem);
            } else {
                $values[] = \call_user_func($serializeItem, $value);
            }
        }

        return '{'.\implode(',', $values).'}';
    }

    /**
     * @internal
     * @todo I think it is wrong.
     */
    private static function escapeString(string $value): string
    {
        return "'".\str_replace('\\', '\\\\', $value)."'";
    }

    /**
     * @internal
     */
    private static function findUnquotedStringEnd(string $string, int $start, int $length): int
    {
        for ($i = $start; $i < $length; $i++) {
            $char = $string[$i];
            if (',' === $char || '}' === $char) {
                return $i - 1;
            }
        }
        throw new TypeConversionError("malformed input: unterminated unquoted string starting at ".$start);
    }

    /**
     * @internal
     */
    private static function findQuotedStringEnd(string $string, int $start, int $length): int
    {
        for ($i = $start; $i < $length; $i++) {
            $char = $string[$i];
            if ('\\' === $char) {
                if ($i === $length) {
                    throw new \Exception(\sprintf("misplaced \\ escape char at end of string"));
                }
                $string[$i++]; // Skip escaped char
            } else if ('"' === $char) {
                return $i;
            }
        }
        throw new TypeConversionError("malformed input: unterminated quoted string starting at ".$start);
    }

    /**
     * @internal
     */
    public static function unescapeString(string $string): string
    {
        return \str_replace('\\\\', '\\', \str_replace('\\"', '"', $string));
    }

    /**
     * @internal
     */
    private static function parseArrayRecursion(string $string, int $start = 0, int $length): array
    {
        $ret = [];

        for ($i = $start; $i < $length; ++$i) {
            $char = $string[$i];
            if (',' === $char) {
                // Next string
            } else if ('{' === $char) {
                list($child, $stop) = self::parseArrayRecursion($string, $i + 1, $length);
                $ret[] = $child;
                $i = $stop;
            } else if ('}' === $char) {
                return [$ret, $i + 1];
            } else {
                if ('"' === $char) {
                    $i++; // Skip start quote
                    $stop = self::findQuotedStringEnd($string, $i, $length);
                    $ret[] = self::unescapeString(\mb_substr($string, $i, $stop - $i));
                } else {
                    $stop = self::findUnquotedStringEnd($string, $i, $length);
                    $ret[] = \mb_substr($string, $i, $stop - $i + 1);
                }
                $i = $stop;
            }
        }

        return [$ret, $length];
    }
}
