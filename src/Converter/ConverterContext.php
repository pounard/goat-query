<?php

declare(strict_types=1);

namespace Goat\Converter;

use Goat\Runner\SessionConfiguration;

final class ConverterContext
{
    private Converter $converter;
    private SessionConfiguration $sessionConfiguration;

    public function __construct(Converter $converter, SessionConfiguration $sessionConfiguration)
    {
        $this->converter = $converter;
        $this->sessionConfiguration = $sessionConfiguration;
    }

    public function getConverter(): Converter
    {
        return $this->converter;
    }

    public function getClientTimeZone(): string
    {
        return $this->sessionConfiguration->getClientTimeZone();
    }

    public function getClientEncoding(): string
    {
        return $this->sessionConfiguration->getClientEncoding();
    }

    public function getSessionConfiguration(): SessionConfiguration
    {
        return $this->sessionConfiguration;
    }
}
