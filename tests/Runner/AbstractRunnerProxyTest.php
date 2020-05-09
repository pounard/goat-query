<?php

declare(strict_types=1);

namespace Goat\Runner\Tests;

use Goat\Runner\AbstractResultIteratorProxy;
use PHPUnit\Framework\TestCase;

/**
 * Ensures that the abstract runner proxy is always complete.
 */
final class AbstractResultIteratorProxyTest extends TestCase
{
    public function testClassIsComplete(): void
    {
        self::expectNotToPerformAssertions();

        // This would crash if methods where missing or signature invalid.
        new class () extends AbstractResultIteratorProxy {};
    }
}
