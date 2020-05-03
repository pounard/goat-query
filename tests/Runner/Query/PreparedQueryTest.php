<?php

declare(strict_types=1);

namespace Goat\Runer\Tests\Query;

use Goat\Query\ExpressionValue;
use Goat\Query\PreparedQuery;
use Goat\Query\Query;
use Goat\Query\QueryBuilder;
use Goat\Query\QueryError;
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
     * Test that it will raise error if not correctly initialized.
     *
      * @dataProvider getRunners
     */
    public function testPreparedQueryErrorWhenNoReturn(Runner $runner, bool $supportsReturning)
    {
        $this->prepare($runner);

        $preparedQuery = $runner->getQueryBuilder()->prepare(
            function () {
                return null;
            }
        );

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/did not return/');
        $preparedQuery->execute([$this->idAdmin]);
    }

    /**
     * Test that it will raise error on subsequent calls.
     *
     * @dataProvider getRunners
     */
    public function testPreparedQueryErrorOnSubsquentCalls(Runner $runner, bool $supportsReturning)
    {
        $this->prepare($runner);

        $preparedQuery = $runner->getQueryBuilder()->prepare(
            function () {
                return null;
            }
        );

        try {
            $preparedQuery->execute([$this->idAdmin]);
        } catch (QueryError $e) {
            // Let it be.
        }

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/not fully initialized/');
        $preparedQuery->execute([$this->idAdmin]);
    }

    /**
     * Test that it will raise error when attempt nesting
     *
      * @dataProvider getRunners
     */
    public function testPreparedQueryErrorWhenNestingAttempt(Runner $runner, bool $supportsReturning)
    {
        $this->prepare($runner);

        $preparedQuery = $runner->getQueryBuilder()->prepare(
            function () use ($runner) {
                return new PreparedQuery($runner, function () {});
            }
        );

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageRegExp('/cannot nest/');
        $preparedQuery->execute([$this->idAdmin]);
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
                    ->where('id_user', ExpressionValue::create(null, 'int'))
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
