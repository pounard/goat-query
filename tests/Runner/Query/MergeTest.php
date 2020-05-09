<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query;

use Goat\Query\SelectQuery;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

class MergetTest extends DatabaseAwareQueryTest
{
    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner, ?string $schema): void
    {
        $builder = $runner->getQueryBuilder();

        $runner->execute("
            create temporary table table1 (
                id integer primary key,
                foo integer not null,
                fizz varchar(255)
            )
        ");

        $builder
            ->insert('table1')
            ->columns(['id', 'foo', 'fizz'])
            ->values([1, 1, 'a'])
            ->values([2, 2, 'b'])
            ->values([3, 3, 'c'])
            ->execute()
        ;

        $runner->execute("
            create temporary table table2 (
                id integer primary key,
                bar integer not null,
                buzz varchar(255)
            )
        ");

        $builder
            ->insert('table2')
            ->columns(['id', 'bar', 'buzz'])
            ->values([1, 1, 'd'])
            ->values([3, 3, 'e'])
            ->values([5, 5, 'f'])
            ->execute()
        ;

        $runner->execute("
            create temporary table table3 (
                id integer not null,
                type varchar(64) not null,
                value text not null,
                primary key (id, type)
            )
        ");

        $builder
            ->insert('table3')
            ->columns(['id', 'type', 'value'])
            ->values([1, 'foo', '1-foo'])
            ->values([1, 'bar', '1-bar'])
            ->values([2, 'foo', '2-foo'])
            ->execute()
        ;
    }

    private function createTable2Select(): SelectQuery
    {
        return (new SelectQuery('table2'))
            ->column('id')
            ->column('bar')
            ->column('buzz')
        ;
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testMergeWithQueryWhenConflictWithIgnore(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $runner
            ->getQueryBuilder()
            ->merge('table1')
            ->setKey(['id'])
            ->columns(['id', 'foo', 'fizz'])
            ->query(
                $this
                    ->createTable2Select()
                    ->condition('id', [1, 3])
            )
            ->onConflictIgnore()
            ->perform()
        ;

        $result = $runner->execute("select id, foo, fizz from table1 order by id asc");

        self::assertSame(3, $result->count());
        self::assertSame(
            [
                ['id' => 1, 'foo' => 1, 'fizz' => 'a'],
                ['id' => 2, 'foo' => 2, 'fizz' => 'b'],
                ['id' => 3, 'foo' => 3, 'fizz' => 'c'],
            ],
            \iterator_to_array($result)
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testMergeWithQueryWhenConflict(TestDriverFactory $factory)
    {
        self::markTestSkipped("This test gives error because column names in SELECT are different from column names in INSERT");

        $runner = $factory->getRunner();

        $runner
            ->getQueryBuilder()
            ->merge('table1')
            ->setKey(['id'])
            ->columns(['id', 'foo', 'fizz'])
            ->query(
                $this
                    ->createTable2Select()
                    ->condition('id', [1, 3, 5])
            )
            ->onConflictUpdate()
            ->perform()
        ;

        $result = $runner->execute("select id, foo, fizz from table1 order by id asc");

        self::assertSame(4, $result->count());
        self::assertSame(
            [
                ['id' => 1, 'foo' => 1, 'fizz' => 'd'],
                ['id' => 2, 'foo' => 2, 'fizz' => 'b'],
                ['id' => 3, 'foo' => 3, 'fizz' => 'e'],
                ['id' => 5, 'foo' => 5, 'fizz' => 'f'],
            ],
            \iterator_to_array($result)
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testMergeWithValuesWhenConflictWithIgnore(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $runner
            ->getQueryBuilder()
            ->merge('table1')
            ->setKey(['id'])
            ->columns(['id', 'foo', 'fizz'])
            ->values([1, 1, 'd'])
            ->values([3, 3, 'e'])
            ->onConflictIgnore()
            ->perform()
        ;

        $result = $runner->execute("select id, foo, fizz from table1 order by id asc");

        self::assertSame(3, $result->count());
        self::assertSame(
            [
                ['id' => 1, 'foo' => 1, 'fizz' => 'a'],
                ['id' => 2, 'foo' => 2, 'fizz' => 'b'],
                ['id' => 3, 'foo' => 3, 'fizz' => 'c'],
            ],
            \iterator_to_array($result)
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testMergeWithValuesWhenConflict(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $runner
            ->getQueryBuilder()
            ->merge('table1')
            ->setKey(['id'])
            ->columns(['id', 'foo', 'fizz'])
            ->values([1, 1, 'd'])
            ->values([3, 3, 'e'])
            ->values([5, 5, 'f'])
            ->onConflictUpdate()
            ->perform()
        ;

        $result = $runner->execute("select id, foo, fizz from table1 order by id asc");

        self::assertSame(4, $result->count());
        self::assertSame(
            [
                ['id' => 1, 'foo' => 1, 'fizz' => 'd'],
                ['id' => 2, 'foo' => 2, 'fizz' => 'b'],
                ['id' => 3, 'foo' => 3, 'fizz' => 'e'],
                ['id' => 5, 'foo' => 5, 'fizz' => 'f'],
            ],
            \iterator_to_array($result)
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testMergeValuesWithReturning(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        if (!$runner->getPlatform()->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $result = $runner
            ->getQueryBuilder()
            ->merge('table1')
            ->setKey(['id'])
            ->columns(['id', 'foo', 'fizz'])
            ->values([3, 3, 'e'])
            ->values([5, 5, 'f'])
            ->onConflictUpdate()
            ->returning('*')
            ->execute()
        ;

        self::assertSame(2, $result->count());
        self::assertSame(
            [
                ['id' => 3, 'foo' => 3, 'fizz' => 'e'],
                ['id' => 5, 'foo' => 5, 'fizz' => 'f'],
            ],
            \iterator_to_array($result)
        );
    }
}
