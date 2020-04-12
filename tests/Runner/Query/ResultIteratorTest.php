<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query;

use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

class ResultIteratorTest extends DatabaseAwareQueryTest
{
    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner, ?string $schema): void
    {
        $runner->execute("
            create temporary table some_table (
                id serial primary key,
                foo integer not null,
                bar varchar(255)
            )
        ");

        $runner
            ->getQueryBuilder()
            ->insertValues('some_table')
            ->columns(['foo', 'bar'])
            ->values([7, 'a'])
            ->values([11, 'a'])
            ->values([7, 'b'])
            ->values([13, 'a'])
            ->values([13, 'b'])
            ->execute()
        ;
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testNonRewindableIteratorCanIterOnlyOnce(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table')
            ->execute()
        ;

        self::assertSame(5, $result->countRows());

        $count = 0;
        foreach ($result as $row) {
            ++$count;
            self::assertIsArray($row);
        }
        self::assertSame(5, $count);

        foreach ($result as $row) {
            self::fail("Non rewindable iterator should not have itered a second twice.");
        }
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testNonRewindableIteratorCanIterOnlyOnceWithFetch(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table')
            ->execute()
        ;

        for ($i = 0; $i < 5; ++$i) {
            self::assertIsArray($result->fetch());
        }

        $result->rewind();

        self::assertNull($result->fetch());
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testRewindableIteratorCanIterTwice(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table')
            ->execute()
            ->setRewindable(true)
        ;

        self::assertSame(5, $result->countRows());

        $count = 0;
        foreach ($result as $row) {
            ++$count;
            self::assertIsArray($row);
        }
        self::assertSame(5, $count);

        $count = 0;
        foreach ($result as $row) {
            ++$count;
            self::assertIsArray($row);
        }
        self::assertSame(5, $count);
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testRewindableIteratorCanIterTwiceWithFetch(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table')
            ->execute()
            ->setRewindable(true)
        ;

        for ($i = 0; $i < 5; ++$i) {
            self::assertIsArray($result->fetch());
        }

        $result->rewind();

        for ($i = 0; $i < 5; ++$i) {
            self::assertIsArray($result->fetch());
        }
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testRewindableIteratorCanResumeIncompleteIteration(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table')
            ->execute()
            ->setRewindable(true)
        ;

        $count = 0;
        foreach ($result as $row) {
            ++$count;
            self::assertIsArray($row);
            if (3 === $count) {
                break;
            }
        }

        $result->rewind();

        $count = 0;
        foreach ($result as $row) {
            ++$count;
            self::assertIsArray($row);
        }
        self::assertSame(5, $count);
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testRewindableIteratorCanResumeIncompleteIterationWithFetch(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table')
            ->execute()
            ->setRewindable(true)
        ;

        for ($i = 0; $i < 3; ++$i) {
            self::assertIsArray($result->fetch());
        }

        $result->rewind();

        for ($i = 0; $i < 5; ++$i) {
            self::assertIsArray($result->fetch());
        }
    }
}
