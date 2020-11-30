<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query;

use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

final class DateWithoutTimeZoneTest extends DatabaseAwareQueryTest
{
    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner, ?string $schema): void
    {
        switch ($runner->getDriverName()) {
            case 'pgsql':
                $runner->perform(
                    <<<SQL
                    CREATE TEMPORARY TABLE some_table (
                        id INT PRIMARY KEY,
                        foo_at timestamp DEFAULT now()
                    )
                    SQL
                );
                break;

            default:
                $runner->perform(
                    <<<SQL
                    CREATE TEMPORARY TABLE some_table (
                        id INT PRIMARY KEY,
                        foo_at datetime DEFAULT now()
                    )
                    SQL
                );
                break;
        }
    }

    /**
     * Store and fetch a date without specifying any time zone, ensure it's
     * the same. It should always work, whatever is the test machine own time
     * zone.
     *
     * @dataProvider runnerDataProvider
     */
    public function testDateStorageDontCareYolo(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();

        $date = new \DateTimeImmutable('2020-11-30 09:18:27');

        $runner->execute('INSERT INTO some_table (id, foo_at) VALUES (1, ?)', [$date]);

        $return = $runner->execute('SELECT foo_at FROM some_table WHERE id = 1')->fetchField();
        \assert($return instanceof \DateTimeImmutable);

        self::assertSame(
            @\date_default_timezone_get(),
            $return->getTimezone()->getName()
        );

        self::assertSame(
            $date->getTimezone()->getName(),
            $return->getTimezone()->getName()
        );

        self::assertSame(
            $date->format(\DateTime::ISO8601),
            $return->format(\DateTime::ISO8601)
        );
    }

    /**
     * Store and fetch a date, ensure it's the same.
     *
     * @dataProvider runnerDataProvider
     */
    public function testDateStorageUsingUtc(TestDriverFactory $factory): void
    {
        $retoreTimeZoneTo = @\date_default_timezone_get() ?? 'UTC';
        try {
            @\date_default_timezone_set('UTC');

            $runner = $factory->getRunner();

            // Christmas Island Time (UTC+7).
            $arbitraryTimeZone = 'Indian/Christmas';
            $date = new \DateTimeImmutable('2020-11-30 09:18:27', new \DateTimeZone($arbitraryTimeZone));

            $runner->execute('INSERT INTO some_table (id, foo_at) VALUES (1, ?)', [$date]);

            $return = $runner->execute('SELECT foo_at FROM some_table WHERE id = 1')->fetchField();
            \assert($return instanceof \DateTimeImmutable);

            self::assertSame(
                @\date_default_timezone_get(),
                $return->getTimezone()->getName()
            );

            self::assertSame(
                '2020-11-30T03:18:27+0100',
                $return->setTimezone(new \DateTimeZone('Europe/Paris'))->format(\DateTime::ISO8601)
            );
        } finally {
            @\date_default_timezone_set($retoreTimeZoneTo);
        }
    }

    /**
     * Store and fetch a date, ensure it's the same.
     *
     * @dataProvider runnerDataProvider
     */
    public function testDateStorageUsingArbitraryTimeZone(TestDriverFactory $factory): void
    {
        $retoreTimeZoneTo = @\date_default_timezone_get() ?? 'UTC';
        try {
            // Tahiti (UTC-10).
            @\date_default_timezone_set('Pacific/Tahiti');
            $clientTimeZoneObject = new \DateTimeZone('Pacific/Tahiti');

            $runner = $factory->getRunner();

            // Christmas Island Time (UTC+7).
            $arbitraryTimeZone = 'Indian/Christmas';
            $date = new \DateTimeImmutable('2020-11-30 09:18:27', new \DateTimeZone($arbitraryTimeZone));

            $runner->execute('INSERT INTO some_table (id, foo_at) VALUES (1, ?)', [$date]);

            $return = $runner->execute('SELECT foo_at FROM some_table WHERE id = 1')->fetchField();
            \assert($return instanceof \DateTimeImmutable);

            self::assertSame(
                @\date_default_timezone_get(),
                $return->getTimezone()->getName()
            );

            self::assertSame(
                '2020-11-29T16:18:27-1000',
                $return->setTimezone($clientTimeZoneObject)->format(\DateTime::ISO8601)
            );
        } finally {
            @\date_default_timezone_set($retoreTimeZoneTo);
        }
    }
}
