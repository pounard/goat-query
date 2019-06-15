<?php

declare(strict_types=1);

namespace Goat\Runer\Tests\Query;

use Goat\Query\ExpressionValue;
use Goat\Query\Query;
use Goat\Query\QueryBuilder;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;

class PreparedQueryTest extends DatabaseAwareQueryTest
{
    private $idAdmin = 1;
    private $idJean = 2;

    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(Runner $runner)
    {
        $runner->perform("
            create temporary table some_table (
                id serial primary key,
                foo integer not null,
                bar varchar(255),
                baz timestamp default now(),
                id_user integer
            )
        ");
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner)
    {
        $runner
            ->getQueryBuilder()
            ->insertValues('some_table')
            ->columns(['foo', 'bar', 'id_user'])
            ->values([42, 'a', $this->idAdmin])
            ->values([666, 'b', $this->idAdmin])
            ->values([37, 'c', $this->idJean])
            ->values([11, 'd', $this->idJean])
            ->values([27, 'e', $this->idAdmin])
            ->execute()
        ;
    }

    /**
     * @dataProvider getRunners
     */
    public function testPrepareSelect(Runner $runner, bool $supportsReturning)
    {
        $this->prepare($runner);

        $preparedQuery = $runner->getQueryBuilder()->prepare(
            function (QueryBuilder $builder) {
                return $builder
                    ->select('some_table')
                    ->column('*')
                    ->condition('id_user', ExpressionValue::create(null, 'int'))
                    ->orderBy('bar', Query::ORDER_DESC)
                ;
            }
        );

        $result = $preparedQuery->execute([$this->idAdmin]);
        $this->assertSame(3, $result->countRows());
        $this->assertSame([27, 666, 42], $result->fetchColumn('foo'));

        $result = $preparedQuery->execute([$this->idJean]);
        $this->assertSame(2, $result->countRows());
        $this->assertSame([11, 37], $result->fetchColumn('foo'));
    }
}
