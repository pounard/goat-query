<?php

namespace Goat\Runner\Tests;

use Goat\Query\ExpressionRaw;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;

class SimpleQueryTest extends DatabaseAwareQueryTest
{
    /**
     * @dataProvider getRunners
     */
    public function testSelectOne(Runner $runner, bool $supportsReturning)
    {
        $this->assertSame(13, $runner->execute("SELECT 13")->fetchField());
    }

    /**
     * @dataProvider getRunners
     */
    public function testSelectOneAsQuery(Runner $runner, bool $supportsReturning)
    {
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
     * @dataProvider getRunners
     */
    public function testPerformOne(Runner $runner, bool $supportsReturning)
    {
        $this->assertSame(1, $runner->perform("SELECT 1"));
    }
}
