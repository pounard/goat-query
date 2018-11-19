<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\Writer\DefaultFormatter;

trait BuilderTestTrait
{
    private function normalize($string)
    {
        $string = \preg_replace('@\s*(\(|\))\s*@ms', '$1', $string);
        $string = \preg_replace('@\s*,\s*@ms', ',', $string);
        $string = \preg_replace('@\s+@ms', ' ', $string);
        $string = \strtolower($string);
        $string = \trim($string);

        return $string;
    }

    protected function assertSameSql($expected, $actual)
    {
        return $this->assertSame(
            $this->normalize($expected),
            $this->normalize($actual)
        );
    }

    protected function createStandardFormatter()
    {
        return new DefaultFormatter(new NullEscaper());
    }
}
