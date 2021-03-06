<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query;

use Goat\Query\QueryError;
use Goat\Runner\EmptyResultIterator;
use Goat\Runner\PagerResultIterator;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

class PagerTest extends DatabaseAwareQueryTest
{
    private $idAdmin;
    private $idJean;

    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner, ?string $schema): void
    {
        $runner->execute("
            create temporary table some_table (
                id serial primary key,
                foo integer not null
            )
        ");

        $query = $runner->getQueryBuilder()->insertValues('some_table');
        for ($i = 0; $i < 157; ++$i) {
            $query->values(['foo' => $i]);
        }
        $query->execute();
    }

    public function testPagerFailsWithNegativePage()
    {
        $result = new EmptyResultIterator();

        $this->expectException(QueryError::class);
        new PagerResultIterator($result, 0, 10, -1);
    }

    public function testPagerFailsWithNegativeLimit()
    {
        $result = new EmptyResultIterator();

        $this->expectException(QueryError::class);
        new PagerResultIterator($result, 0, -1, 1);
    }

    /** @dataProvider runnerDataProvider */
    public function testPagerBasics(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $query = $runner->getQueryBuilder()->select('some_table')->column('foo')->range(7, 14);

        $iterator = new PagerResultIterator($query->execute(), 157, 7, 3);
        $this->assertSame(7, $iterator->getCurrentCount());
        $this->assertSame(14, $iterator->getStartOffset());
        $this->assertSame(21, $iterator->getStopOffset());
        $this->assertSame(23, $iterator->getLastPage());
        $this->assertSame(3, $iterator->getCurrentPage());
        $this->assertSame(157, $iterator->getTotalCount());
        $this->assertTrue($iterator->hasNextPage());
        $this->assertTrue($iterator->hasPreviousPage());
        $this->assertSame(7, $iterator->getLimit());
    }

    /** @dataProvider runnerDataProvider */
    public function testPagerFirstPage(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $query = $runner->getQueryBuilder()->select('some_table')->column('foo')->range(50, 0);

        $iterator = new PagerResultIterator($query->execute(), 157, 50, 1);
        $this->assertSame(50, $iterator->getCurrentCount());
        $this->assertSame(0, $iterator->getStartOffset());
        $this->assertSame(50, $iterator->getStopOffset());
        $this->assertTrue($iterator->hasNextPage());
        $this->assertFalse($iterator->hasPreviousPage());
    }

    /** @dataProvider runnerDataProvider */
    public function testPagerLastPage(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $query = $runner->getQueryBuilder()->select('some_table')->column('foo')->range(50, 150);

        $iterator = new PagerResultIterator($query->execute(), 157, 50, 4);
        $this->assertSame(7, $iterator->getCurrentCount());
        $this->assertSame(150, $iterator->getStartOffset());
        $this->assertSame(157, $iterator->getStopOffset());
        $this->assertFalse($iterator->hasNextPage());
        $this->assertTrue($iterator->hasPreviousPage());
    }
}
