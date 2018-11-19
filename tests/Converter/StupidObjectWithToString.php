<?php

declare(strict_types=1);

namespace Goat\Converter\Tests;

class StupidObjectWithToString
{
    public function __toString()
    {
        return "I am a string";
    }
}
