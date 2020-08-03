<?php

declare(strict_types=1);

namespace Goat\Runner\Testing;

use Goat\Runner\Runner;
use PHPUnit\Framework\TestCase;

abstract class DatabaseAwareQueryTest extends TestCase
{
    use DatabaseAwareQueryTestTrait;
}
