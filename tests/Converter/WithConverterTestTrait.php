<?php

declare(strict_types=1);

namespace Goat\Converter\Tests;

use Goat\Converter\Converter;
use Goat\Converter\ConverterContext;
use Goat\Converter\DefaultConverter;
use Goat\Runner\SessionConfiguration;

trait WithConverterTestTrait
{
    protected static function context(
        ?Converter $converter = null,
        ?SessionConfiguration $sessionConfiguration = null
    ): ConverterContext {
        return new ConverterContext(
            $converter ?? self::defaultConverter(),
            $sessionConfiguration ?? SessionConfiguration::empty()
        );
    }

    protected static function contextWithTimeZone(string $clientTimeZone, ?Converter $converter = null): ConverterContext
    {
        return self::context(
            $converter ?? self::defaultConverter(),
            new SessionConfiguration('UTF-8', $clientTimeZone, 'null', 'null', [])
        );
    }

    protected static function defaultConverter(): ?Converter
    {
        return new DefaultConverter();
    }
}
