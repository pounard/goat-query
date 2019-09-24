<?php

declare(strict_types=1);

namespace Goat\Runer\Tests\Query;

use Goat\Runner\QueryPagerResultIterator;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;

class QueryPagerTest extends DatabaseAwareQueryTest
{
    private $idAdmin;
    private $idJean;

    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(Runner $runner)
    {
        $runner->execute("
            create temporary table some_table (
                id serial primary key,
                foo integer not null
            )
        ");
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner)
    {
        $query = $runner->getQueryBuilder()->insertValues('some_table');
        for ($i = 0; $i < 157; ++$i) {
            $query->values(['foo' => $i]);
        }
        $query->execute();
    }

    public function testPagerFailsWithNegativePage()
    {
        $this->markTestIncomplete("Implement me");
    }

    public function testPagerFailsWithNegativeLimit()
    {
        $this->markTestIncomplete("Implement me");
    }

    /**
     * @dataProvider getRunners
     */
    public function testPagerBasics(Runner $runner)
    {
        $this->prepare($runner);

        $query = $runner->getQueryBuilder()->select('some_table')->column('foo');

        $iterator = (new QueryPagerResultIterator($query))
            ->setLimit(7)
            ->setPage(3)
        ;

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

    /**
     * @dataProvider getRunners
     */
    public function testPagerFirstPage(Runner $runner)
    {
        $this->prepare($runner);

        $query = $runner->getQueryBuilder()->select('some_table')->column('foo');

        $iterator = (new QueryPagerResultIterator($query))
            ->setLimit(50)
            ->setPage(1)
        ;

        $this->assertSame(50, $iterator->getCurrentCount());
        $this->assertSame(0, $iterator->getStartOffset());
        $this->assertSame(50, $iterator->getStopOffset());
        $this->assertTrue($iterator->hasNextPage());
        $this->assertFalse($iterator->hasPreviousPage());
    }

    /**
     * @dataProvider getRunners
     */
    public function testPagerLastPage(Runner $runner)
    {
        $this->prepare($runner);

        $query = $runner->getQueryBuilder()->select('some_table')->column('foo');

        $iterator = (new QueryPagerResultIterator($query))
            ->setLimit(50)
            ->setPage(4)
        ;

        //$this->assertSame(7, $iterator->getCurrentCount());
        $this->assertSame(150, $iterator->getStartOffset());
        $this->assertSame(157, $iterator->getStopOffset());
        $this->assertFalse($iterator->hasNextPage());
        $this->assertTrue($iterator->hasPreviousPage());
    }
}
