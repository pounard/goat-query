<?php

declare(strict_types=1);

namespace Goat\Converter;

use Goat\Runner\SessionConfiguration;

final class ConverterContext
{
    private ConverterInterface $converter;
    private SessionConfiguration $sessionConfiguration;

    public function __construct(ConverterInterface $converter, SessionConfiguration $sessionConfiguration)
    {
        $this->converter = $converter;
        $this->sessionConfiguration = $sessionConfiguration;
    }

    public function getConverter(): ConverterInterface
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
