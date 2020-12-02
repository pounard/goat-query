<?php

declare(strict_types=1);

namespace Goat\Converter\Tests;

use Goat\Converter\ConverterContext;
use Goat\Runner\SessionConfiguration;
use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;

trait WithConverterTestTrait
{
    protected static function context(
        ?ConverterInterface $converter = null,
        ?SessionConfiguration $sessionConfiguration = null
    ): ConverterContext {
        return new ConverterContext(
            $converter ?? self::defaultConverter(),
            $sessionConfiguration ?? SessionConfiguration::empty()
        );
    }

    protected static function contextWithTimeZone(string $clientTimeZone, ?ConverterInterface $converter = null): ConverterContext
    {
        return self::context(
            $converter ?? self::defaultConverter(),
            new SessionConfiguration('UTF-8', $clientTimeZone, 'null', 'null', [])
        );
    }

    protected static function defaultConverter(): ?ConverterInterface
    {
        return new DefaultConverter();
    }
}
