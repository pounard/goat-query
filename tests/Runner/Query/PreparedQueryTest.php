<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query;

use Goat\Query\PreparedQuery;
use Goat\Query\Query;
use Goat\Query\QueryBuilder;
use Goat\Query\QueryError;
use Goat\Query\Expression\ValueExpression;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

class PreparedQueryTest extends DatabaseAwareQueryTest
{
    private $idAdmin = 1;
    private $idJean = 2;

    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner, ?string $schema): void
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
     * @dataProvider runnerDataProvider
     */
    public function testPreparedQueryErrorWhenNoReturn(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $preparedQuery = $runner->getQueryBuilder()->prepare(
            function () {
                return null;
            }
        );

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageMatches('/did not return/');
        $preparedQuery->execute([$this->idAdmin]);
    }

    /**
     * Test that it will raise error on subsequent calls.
     *
     * @dataProvider runnerDataProvider
     */
    public function testPreparedQueryErrorOnSubsquentCalls(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

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
        $this->expectExceptionMessageMatches('/not fully initialized/');
        $preparedQuery->execute([$this->idAdmin]);
    }

    /**
     * Test that it will raise error when attempt nesting
     *
      * @dataProvider runnerDataProvider
     */
    public function testPreparedQueryErrorWhenNestingAttempt(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $preparedQuery = $runner->getQueryBuilder()->prepare(
            function () use ($runner) {
                return new PreparedQuery($runner, function () {});
            }
        );

        $this->expectException(QueryError::class);
        $this->expectExceptionMessageMatches('/cannot nest/');
        $preparedQuery->execute([$this->idAdmin]);
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testPrepareSelect(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $preparedQuery = $runner->getQueryBuilder()->prepare(
            function (QueryBuilder $builder) {
                return $builder
                    ->select('some_table')
                    ->column('*')
                    ->where('id_user', new ValueExpression(null, 'int'))
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
