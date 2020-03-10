<?php

namespace Goat\Runner\Tests;

use Goat\Query\ExpressionRaw;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

class SimpleQueryTest extends DatabaseAwareQueryTest
{
    /**
     * @dataProvider runnerDataProvider
     */
    public function testSelectOne(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $this->assertSame(13, $runner->execute("SELECT 13")->fetchField());
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testSelectOneAsQuery(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $this->assertSame(
            42,
            $runner
                ->getQueryBuilder()
                ->select()
                ->columnExpression(ExpressionRaw::create('42'))
                ->execute()
                ->fetchField()
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testPerformOne(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $this->assertSame(1, $runner->perform("SELECT 1"));
    }
}
