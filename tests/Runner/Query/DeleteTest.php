<?php

declare(strict_types=1);

namespace Goat\Runer\Tests\Query;

use Goat\Runner\Runner;
use Goat\Tests\Driver\Mock\DeleteSomeTableWithUser;
use Goat\Runner\Testing\DatabaseAwareQueryTest;

class DeleteTest extends DatabaseAwareQueryTest
{
    private $idAdmin;
    private $idJean;

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
        $runner->perform("
            create temporary table users (
                id serial primary key,
                name varchar(255)
            )
        ");
        $runner->perform("
            create temporary table users_status (
                id_user integer,
                status integer
            )
        ");
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner)
    {
        $runner
            ->insertValues('users')
            ->columns(['name'])
            ->values(["admin"])
            ->values(["jean"])
            ->execute()
        ;

        $idList = $runner
            ->select('users')
            ->column('id')
            ->orderBy('name')
            ->execute()
            ->fetchColumn()
        ;

        $this->idAdmin = $idList[0];
        $this->idJean = $idList[1];

        $runner
            ->insertValues('users_status')
            ->columns(['id_user', 'status'])
            ->values([$this->idAdmin, 7])
            ->values([$this->idJean, 11])
            ->values([$this->idJean, 17])
            ->execute()
        ;

        $runner
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
     * Test simple DELETE FROM WHERE
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteWhere($runner, $class)
    {
        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $runner
            ->delete('some_table', 't')
            ->condition('t.id_user', $this->idJean)
            ->execute()
        ;
        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());

        $result = $runner
            ->delete('some_table')
            ->condition('bar', 'a')
            ->execute()
        ;
        $this->assertSame(1, $result->countRows());
        $this->assertSame(2, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where bar = $*::varchar", ['a'])->fetchField());

        // For fun, test with a named parameter
        $result = $runner
            ->delete('some_table')
            ->condition('bar', ':bar::varchar')
            ->execute([
                'bar' => 'e',
            ])
        ;
        $this->assertSame(1, $result->countRows());
        $this->assertSame(1, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(1, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where bar = $*::varchar", ['e'])->fetchField());
    }

    /**
     * Test simple DELETE FROM
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteAll($runner, $class)
    {
        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $runner->delete('some_table')->execute();
        $this->assertSame(5, $result->countRows());
        $this->assertSame(0, $runner->execute("select count(*) from some_table")->fetchField());
    }

    /**
     * Test DELETE FROM WHERE IN (SELECT ... )
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteWhereIn($runner, $class)
    {
        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $whereInSelect = $runner
            ->select('users')
            ->column('id')
            ->condition('name', 'jean')
        ;

        $result = $runner
            ->delete('some_table')
            ->condition('id_user', $whereInSelect)
            ->execute()
        ;
        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());
    }

    /**
     * Test DELETE FROM USING WHERE
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteUsing($runner, $class)
    {
        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $runner
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.id', $this->idJean)
            ->execute()
        ;
        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());
    }

    /**
     * Test simple DELETE FROM USING RETURNING
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteUsingReturning($runner, $class)
    {
        if (!$runner->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $runner
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.id', $this->idJean)
            ->returning('t.id')
            ->returning('t.id_user')
            ->returning('u.name')
            ->returning('t.bar')
            ->execute()
        ;
        $this->assertCount(2, $result);
        $this->assertSame(3, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());

        foreach ($result as $row) {
            $this->assertSame($this->idJean, $row['id_user']);
            $this->assertSame('jean', $row['name']);
            $this->assertInternalType('integer', $row['id']);
            $this->assertInternalType('string', $row['bar']);
        }
    }

    /**
     * Test simple DELETE FROM USING RETURNING
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteUsingReturningAndHydrating($runner, $class)
    {
        if (!$runner->supportsReturning()) {
            $this->markTestIncomplete("driver does not support RETURNING");
        }

        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $runner
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.id', $this->idJean)
            ->returning('t.id')
            ->returning('t.id_user', 'userId')
            ->returning('u.name')
            ->returning('t.bar')
            ->execute([], DeleteSomeTableWithUser::class)
        ;
        $this->assertCount(2, $result);
        $this->assertSame(3, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());

        foreach ($result as $row) {
            $this->assertTrue($row instanceof DeleteSomeTableWithUser);
            $this->assertSame($this->idJean, $row->getUserId());
            $this->assertSame('jean', $row->getUserName());
            $this->assertInternalType('integer', $row->getId());
            $this->assertInternalType('string', $row->getBar());
        }
    }

    /**
     * Test DELETE FROM USING JOIN WHERE
     *
     * @dataProvider driverDataSource
     */
    public function testDeleteUsingJoin($runner, $class)
    {
        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());

        $result = $runner
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->join('users_status', 'u.id = st.id_user', 'st')
            ->condition('st.status', 5) // Does nothing
            ->execute()
        ;

        $this->assertSame(0, $result->countRows());
        $this->assertSame(5, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(2, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());

        $result = $runner
            ->delete('some_table', 't')
            ->join('users', 'u.id = t.id_user', 'u')
            ->join('users_status', 'u.id = st.id_user', 'st')
            ->condition('st.status', 11) // Removes jean
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());
        $this->assertSame(3, $runner->execute("select count(*) from some_table")->fetchField());
        $this->assertSame(0, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idJean])->fetchField());
        $this->assertSame(3, $runner->execute("select count(*) from some_table where id_user = $*::int", [$this->idAdmin])->fetchField());
    }
}
