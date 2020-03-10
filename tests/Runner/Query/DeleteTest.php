<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query;

use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;
use Goat\Runner\Tests\Query\Mock\DeleteSomeTableWithUser;

class DeleteTest extends DatabaseAwareQueryTest
{
    const ID_ADMIN = 1;
    const ID_JEAN = 2;

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
        $runner->perform("
            create temporary table users (
                id integer primary key,
                name varchar(255)
            )
        ");
        $runner->perform("
            create temporary table users_status (
                id_user integer,
                status integer
            )
        ");

        $runner
            ->getQueryBuilder()
            ->insertValues('users')
            ->columns(['name', 'id'])
            ->values(["admin", self::ID_ADMIN])
            ->values(["jean", self::ID_JEAN])
            ->execute()
        ;

        $runner
            ->getQueryBuilder()
            ->insertValues('users_status')
            ->columns(['id_user', 'status'])
            ->values([self::ID_ADMIN, 7])
            ->values([self::ID_JEAN, 11])
            ->values([self::ID_JEAN, 17])
            ->execute()
        ;

        $runner
            ->getQueryBuilder()
            ->insertValues('some_table')
            ->columns(['foo', 'bar', 'id_user'])
            ->values([42, 'a', self::ID_ADMIN])
            ->values([666, 'b', self::ID_ADMIN])
            ->values([37, 'c', self::ID_JEAN])
            ->values([11, 'd', self::ID_JEAN])
            ->values([27, 'e', self::ID_ADMIN])
            ->execute()
        ;
    }

    /**
     * Test simple DELETE FROM WHERE
     *
     * @dataProvider runnerDataProvider
     */
    public function testDeleteWhere(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_ADMIN])->fetchField());

        $result = $runner
            ->getQueryBuilder()
            ->delete('some_table', 't')
            ->condition('t.id_user', self::ID_JEAN)
            ->execute()
        ;
        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_JEAN])->fetchField());

        $result = $runner
            ->getQueryBuilder()
            ->delete('some_table')
            ->condition('bar', 'a')
            ->execute()
        ;
        $this->assertSame(1, $result->countRows());
        $this->assertSame(2, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_ADMIN])->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where bar = ?::varchar", ['a'])->fetchField());
    }

    /**
     * Test simple DELETE FROM
     *
     * @dataProvider runnerDataProvider
     */
    public function testDeleteAll(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_ADMIN])->fetchField());

        $result = $runner->getQueryBuilder()->delete('some_table')->execute();
        $this->assertSame(5, $result->countRows());
        $this->assertSame(0, $runner->execute("select count(*) from some_table")->fetchField());
    }

    /**
     * Test DELETE FROM WHERE IN (SELECT ... )
     *
     * @dataProvider runnerDataProvider
     */
    public function testDeleteWhereIn(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_ADMIN])->fetchField());

        $whereInSelect = $runner
            ->getQueryBuilder()
            ->select('users')
            ->column('id')
            ->condition('name', 'jean')
        ;

        $result = $runner
            ->getQueryBuilder()
            ->delete('some_table')
            ->condition('id_user', $whereInSelect)
            ->execute()
        ;
        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_JEAN])->fetchField());
    }

    /**
     * Test DELETE FROM USING WHERE
     *
     * @dataProvider runnerDataProvider
     */
    public function testDeleteUsing(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_ADMIN])->fetchField());

        $result = $runner
            ->getQueryBuilder()
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.id', self::ID_JEAN)
            ->execute()
        ;
        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_JEAN])->fetchField());
    }

    /**
     * Test simple DELETE FROM USING RETURNING
     *
     * @dataProvider runnerDataProvider
     */
    public function testDeleteUsingReturning(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        if (!$runner->getPlatform()->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_ADMIN])->fetchField());

        $result = $runner
            ->getQueryBuilder()
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.id', self::ID_JEAN)
            ->returning('t.id')
            ->returning('t.id_user')
            ->returning('u.name')
            ->returning('t.bar')
            ->execute()
        ;
        $this->assertCount(2, $result);
        $this->assertSame(3, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_JEAN])->fetchField());

        foreach ($result as $row) {
            $this->assertSame(self::ID_JEAN, $row['id_user']);
            $this->assertSame('jean', $row['name']);
            $this->assertInternalType('integer', $row['id']);
            $this->assertInternalType('string', $row['bar']);
        }
    }

    /**
     * Test simple DELETE FROM USING RETURNING
     *
     * @dataProvider runnerDataProvider
     */
    public function testDeleteUsingReturningAndHydrating(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        if (!$runner->getPlatform()->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_ADMIN])->fetchField());

        $result = $runner
            ->getQueryBuilder()
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.id', self::ID_JEAN)
            ->returning('t.id')
            ->returning('t.id_user', 'userId')
            ->returning('u.name')
            ->returning('t.bar')
            ->execute([], DeleteSomeTableWithUser::class)
        ;
        $this->assertCount(2, $result);
        $this->assertSame(3, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_JEAN])->fetchField());

        foreach ($result as $row) {
            $this->assertTrue($row instanceof DeleteSomeTableWithUser);
            $this->assertSame(self::ID_JEAN, $row->getUserId());
            $this->assertSame('jean', $row->getUserName());
            $this->assertInternalType('integer', $row->getId());
            $this->assertInternalType('string', $row->getBar());
        }
    }

    /**
     * Test DELETE FROM USING JOIN WHERE
     *
     * @dataProvider runnerDataProvider
     */
    public function testDeleteUsingJoin(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_ADMIN])->fetchField());

        $result = $runner
            ->getQueryBuilder()
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->join('users_status', 'u.id = st.id_user', 'st')
            ->condition('st.status', 5) // Does nothing
            ->execute()
        ;

        $this->assertSame(0, $result->countRows());
        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_JEAN])->fetchField());

        $result = $runner
            ->getQueryBuilder()
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->join('users_status', 'u.id = st.id_user', 'st')
            ->condition('st.status', 11) // Removes jean
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_JEAN])->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = ?::int", [self::ID_ADMIN])->fetchField());
    }
}
