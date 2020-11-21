<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query;

use Goat\Query\Query;
use Goat\Query\Expression\RawExpression;
use Goat\Query\Expression\ValueExpression;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;
use Goat\Runner\Tests\Query\Mock\InsertAndTheCatSays;

class InsertTest extends DatabaseAwareQueryTest
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
                bar varchar(255),
                baz timestamp default now()
            )
        ");

        $runner->execute("
            create temporary table users (
                id serial primary key,
                name varchar(255)
            )
        ");

        $runner
            ->getQueryBuilder()
            ->insertValues('users')
            ->columns(['name'])
            ->values(["admin"])
            ->values(["jean"])
            ->execute()
        ;
    }

    /**
     * Very simple test
     *
     * @dataProvider runnerDataProvider
     */
    public function testSingleValueInsert(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $referenceDate = new \DateTime();

        $runner
            ->getQueryBuilder()
            ->insertValues('some_table')
            ->columns(['foo', 'bar', 'baz'])
            // @todo argument conversion on querybuilder!
            ->values([42, 'the big question', $referenceDate->format('Y-m-d H:i:s')])
            ->execute()
        ;

        $value = $runner
            ->getQueryBuilder()
            ->select('some_table', 't')
            ->column('t.foo')
            ->column('t.bar')
            ->column('t.baz', 'date')
            ->orderBy('t.id', Query::ORDER_DESC)
            ->range(1)
            ->execute()
            ->fetch()
        ;

        // PHP 7.1 seems to compare date with millisecs where PHP 7.0 stops
        // at the second granularity
        $this->assertSame(
            $referenceDate->format(\DateTime::ISO8601),
            $value['date']->format(\DateTime::ISO8601)
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testInsertValuesWithExpressionsInConstantTable(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();

        $runner
            ->getQueryBuilder()
            ->insert('some_table')
            ->columns(['foo', 'bar', 'baz'])
            ->values([
                RawExpression::create('11 + 11'),
                'b',
                RawExpression::create('current_timestamp'),
            ])
            ->values([
                42,
                ValueExpression::create('a'),
                RawExpression::create('current_timestamp'),
            ])
            ->execute()
        ;

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table', 't')
            ->orderBy('t.id', Query::ORDER_DESC)
            ->range(2)
            ->execute()
        ;

        $row1 = $result->fetch();
        self::assertSame(42, $row1['foo']);
        self::assertSame('a', $row1['bar']);
        self::assertInstanceOf(\DateTimeInterface::class, $row1['baz']);

        $row2 = $result->fetch();
        self::assertSame(22, $row2['foo']);
        self::assertSame('b', $row2['bar']);
        self::assertInstanceOf(\DateTimeInterface::class, $row2['baz']);
    }

    /**
     * Okay, let's bulk!
     *
     * @dataProvider runnerDataProvider
     */
    public function testBulkValueInsert(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $insert = $runner
            ->getQueryBuilder()
            ->insertValues('some_table')
            ->columns(['foo', 'bar'])
            ->values([1, 'one'])
            // Attempt an SQL injection, this is a simple one
            ->values([666, "); delete from users; select ("])
        ;

        for ($i = 0; $i < 10; ++$i) {
            $ref = rand(0, 255);
            $insert->values([$ref, dechex($ref)]);
        }

        $insert->execute();

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table', 't')
            ->orderBy('t.id', Query::ORDER_ASC)
            ->execute()
        ;

        $this->assertSame(12, $result->count());

        $row1 = $result->fetch();
        $this->assertSame(1, $row1['foo']);

        $row2 = $result->fetch();
        $this->assertSame(666, $row2['foo']);
        $this->assertSame("); delete from users; select (", $row2['bar']);
    }

    /**
     * Test value insert with a RETURNING clause
     *
     * @dataProvider runnerDataProvider
     */
    public function testBulkValueInsertWithReturning(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        // Add one value, so there is data in the table, it will ensure that
        // the returning count is the right one
        $result = $runner
            ->getQueryBuilder()
            ->insertValues('some_table')
            ->columns(['foo', 'bar'])
            ->values([1, 'a'])
            ->values([2, 'b'])
            ->execute();
        ;

        // Queries that don't return anything, in our case, an INSERT query
        // without the RETURNING clause, should not return anything
        $this->assertSame(0, $result->count());

        // But we should have an affected row count
        $this->assertSame(2, $result->countRows());

        // Add one value, so there is data in the table, it will ensure that
        // the returning count is the right one
        $affectedRowCount = $runner
            ->getQueryBuilder()
            ->insertValues('some_table')
            ->columns(['foo', 'bar'])
            ->values([3, 'c'])
            ->values([4, 'd'])
            ->values([5, '8'])
            ->perform();
        ;

        $this->assertSame(3, $affectedRowCount);

        if (!$runner->getPlatform()->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $result = $runner
            ->getQueryBuilder()
            ->insertValues('some_table')
            ->columns(['foo', 'bar'])
            ->returning('id')
            ->returning('bar', 'miaw')
            ->values([12, 'boo'])
            ->values([13, 'baa'])
            ->values([14, 'bee'])
            ->execute();
        ;

        $this->assertSame(3, $result->countRows());

        // 'id' field is a sequence, and should start with 1
        $row1 = $result->fetch();
        $this->assertSame(6, $row1['id']);
        $this->assertNotContains('baz', $row1);
        $this->assertNotContains('bar', $row1);
        $this->assertSame('boo', $row1['miaw']);

        $row2 = $result->fetch();
        $this->assertSame(7, $row2['id']);
        $this->assertNotContains('baz', $row2);
        $this->assertNotContains('bar', $row2);
        $this->assertSame('baa', $row2['miaw']);

        $row3 = $result->fetch();
        $this->assertSame(8, $row3['id']);
        $this->assertNotContains('baz', $row3);
        $this->assertNotContains('bar', $row3);
        $this->assertSame('bee', $row3['miaw']);
    }

    /**
     * Test value insert with a RETURNING clause and object hydration
     *
     * @dataProvider runnerDataProvider
     */
    public function testBulkValueInsertWithReturningAndHydration(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        if (!$runner->getPlatform()->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        // Add one value, so there is data in the table, it will ensure that
        // the returning count is the right one
        $result = $runner
            ->getQueryBuilder()
            ->insertValues('some_table')
            ->columns(['foo', 'bar'])
            ->values([1, 'a'])
            ->values([2, 'b'])
            ->returning('id')
            ->returning('bar', 'miaw')
            ->execute([], InsertAndTheCatSays::class);
        ;

        foreach ($result as $row) {
            $this->assertTrue($row instanceof InsertAndTheCatSays);
            $this->assertIsString($row->miaw());
            $this->assertIsInt($row->getId());
        }
    }

    /**
     * Test a bulk insert from SELECT
     *
     * @dataProvider runnerDataProvider
     */
    public function testBulkInsertFromQuery(TestDriverFactory $factory)
    {
        // $runner = $factory->getRunner();

        $this->markTestIncomplete("not implemented yet");
    }
}
