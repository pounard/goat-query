<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query;

use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;
use Goat\Query\Expression\ValueExpression;

class PgSQLArrayTest extends DatabaseAwareQueryTest
{
    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner, ?string $schema): void
    {
        $runner->execute("
            create temporary table foo (
                id serial primary key,
                bar int[],
                baz varchar[]
            )
        ");
    }

    /**
     * @dataProvider runnerDataProvider 
     */
    public function testAll(TestDriverFactory $factory): void
    {
        if (false === \strpos($factory->getDriverName(), 'pg')) {
            self::markTestSkipped("This test is for PostgreSQL only.");
        }

        $runner = $factory->getRunner();

        $runner
            ->getQueryBuilder()
            ->insert('foo')
            ->values([
                'id' => 1,
                'bar' => new ValueExpression([7, 11, 42], '_int')
            ])
            ->perform()
        ;

        $runner
            ->getQueryBuilder()
            ->insert('foo')
            ->values([
                'id' => 2,
                'baz' => new ValueExpression(['beh', 'bla'], 'varchar[]')
            ])
            ->perform()
        ;

        $value = $runner
            ->getQueryBuilder()
            ->select('foo')
            ->column('bar')
            ->where('id', 1)
            ->execute()
            ->fetchField()
        ;

        self::assertSame([7, 11, 42], $value);

        $value = $runner
            ->getQueryBuilder()
            ->select('foo')
            ->column('baz')
            ->where('id', 2)
            ->execute()
            ->fetchField()
        ;

        self::assertSame(['beh', 'bla'], $value);
    }
}
