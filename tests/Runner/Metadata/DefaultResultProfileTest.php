<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Metadata;

use PHPUnit\Framework\TestCase;
use Goat\Runner\Metadata\DefaultResultProfile;

final class DefaultResultProfileTest extends TestCase
{
    public function testIsNotCompleteUntilExecutionTime(): void
    {
        $profile = new DefaultResultProfile();

        self::assertFalse($profile->isComplete());

        $profile->donePrepare();
        self::assertFalse($profile->isComplete());

        $profile->doneExecute();
        self::assertTrue($profile->isComplete());
    }

    public function testDefaultValuesAreMinusOne(): void
    {
        $profile = new DefaultResultProfile();

        self::assertSame(-1, $profile->getPreparationTime());
        self::assertSame(-1, $profile->getExecutionTime());
        self::assertSame(-2, $profile->getTotalTime());
    }

    public function testIsError(): void
    {
        $profile = new DefaultResultProfile();

        self::assertFalse($profile->isError());
    }

    public function testGettersTime(): void
    {
        $profile = new DefaultResultProfile();

        $profile->donePrepare();
        $profile->doneExecute();

        self::assertGreaterThanOrEqual(0, $profile->getPreparationTime());
        self::assertGreaterThanOrEqual(0, $profile->getExecutionTime());
    }
}
