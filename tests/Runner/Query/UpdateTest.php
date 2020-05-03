<?php

declare(strict_types=1);

namespace Goat\Runer\Tests\Query;

use Goat\Query\ExpressionColumn;
use Goat\Query\ExpressionRaw;
use Goat\Query\Where;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;

class UpdateTest extends DatabaseAwareQueryTest
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
                foo integer not null,
                bar varchar(255),
                baz timestamp default now(),
                id_user integer
            )
        ");
        $runner->execute("
            create temporary table users (
                id serial primary key,
                name varchar(255)
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
     * Update using simple WHERE conditions
     *
     * @dataProvider driverDataSource
     */
    public function testUpdateWhere($runner, $class)
    {
        $result = $runner
            ->update('some_table')
            ->where('foo', 42)
            ->set('foo', 43)
            ->execute()
        ;

        $this->assertSame(1, $result->countRows());

        $result = $runner
            ->select('some_table')
            ->where('foo', 43)
            ->execute()
        ;

        $this->assertSame(1, $result->countRows());
        $this->assertSame('a', $result->fetch()['bar']);

        $query = $runner->update('some_table', 'trout');
        $query
            ->getWhere()
            ->open(Where::OR)
                ->condition('trout.foo', 43)
                ->condition('trout.foo', 666)
            ->close()
        ;

        $result = $query
            ->set('bar', 'cassoulet')
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());
    }

    /**
     * Update using FROM ... JOIN statements
     *
     * @dataProvider driverDataSource
     */
    public function testUpdateJoin($runner, $class)
    {
        $result = $runner
            ->update('some_table', 't')
            ->set('foo', 127)
            ->join('users', "u.id = t.id_user", 'u')
            ->where('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $runner
            ->select('some_table', 'roger')
            ->join('users', 'john.id = roger.id_user', 'john')
            ->where('john.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame(127, $row['foo']);
        }

        $result = $runner
            ->select('some_table')
            ->where('foo', 127)
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
    }

    /**
     * Update using a IN (SELECT ...)
     *
     * @dataProvider driverDataSource
     */
    public function testUpdateWhereIn($runner, $class)
    {
        $selectInQuery = $runner
            ->select('users')
            ->column('id')
            ->where('name', 'admin')
        ;

        $result = $runner
            ->update('some_table', 't')
            ->set('foo', 127)
            ->where('t.id_user', $selectInQuery)
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $runner
            ->select('some_table', 'roger')
            ->join('users', 'john.id = roger.id_user', 'john')
            ->where('john.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame(127, $row['foo']);
        }

        $result = $runner
            ->select('some_table')
            ->where('foo', 127)
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
    }

    /**
     * Update RETURNING
     *
     * @dataProvider driverDataSource
     */
    public function testUpateReturning($runner, $class)
    {
        if (!$runner->supportsReturning()) {
            $this->markTestSkipped("driver does not support RETURNING");
        }

        $result = $runner
            ->update('some_table', 't')
            ->set('foo', 127)
            ->join('users', "u.id = t.id_user", 'u')
            ->where('u.name', 'admin')
            ->returning(new ExpressionRaw('*'))
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame(127, $row['foo']);
            $this->assertSame('admin', $row['name']);
        }
    }

    /**
     * Update by using SET column = other_table.column from FROM using ExpressionColumn
     *
     * @dataProvider driverDataSource
     */
    public function testUpateSetExpressionColumn($runner, $class)
    {
        $result = $runner
            ->update('some_table', 't')
            ->set('foo', new ExpressionColumn('u.id'))
            ->join('users', "u.id = t.id_user", 'u')
            ->where('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $runner
            ->select('some_table', 't')
            ->columns(['t.foo', 't.id_user'])
            ->join('users', 'u.id = t.id_user', 'u')
            ->where('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame($row['id_user'], $row['foo']);
        }
    }

    /**
     * Update by using SET column = other_table.column from FROM using ExpressionRaw
     *
     * @dataProvider driverDataSource
     */
    public function testUpateSetExpressionRaw($runner, $class)
    {
        $result = $runner
            ->update('some_table', 't')
            ->set('foo', new ExpressionRaw('u.id'))
            ->join('users', "u.id = t.id_user", 'u')
            ->where('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $runner
            ->select('some_table', 't')
            ->columns(['t.foo', 't.id_user'])
            ->join('users', 'u.id = t.id_user', 'u')
            ->where('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame($row['id_user'], $row['foo']);
        }
    }

    /**
     * Update by using SET column = (SELECT ...)
     *
     * @dataProvider driverDataSource
     */
    public function testUpateSetSelectQuery($runner, $class)
    {
        $selectValueQuery = $runner
            ->select('users', 'z')
            ->columnExpression('z.id + 7')
            ->whereExpression('z.id = id_user')
        ;

        $result = $runner
            ->update('some_table')
            ->set('foo', $selectValueQuery)
            ->where('id_user', $this->idJean)
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());

        $result = $runner
            ->select('some_table')
            ->where('id_user', $this->idJean)
            ->execute()
        ;
        foreach ($result as $row) {
            $this->assertSame($row['id_user'] + 7, $row['foo']);
        }

        $result = $runner
            ->select('some_table')
            ->where('id_user', $this->idAdmin)
            ->execute()
        ;
        foreach ($result as $row) {
            $this->assertNotSame($row['id_user'] + 7, $row['foo']);
        }
    }

    /**
     * Update by using SET column = some_statement()
     *
     * @dataProvider driverDataSource
     */
    public function testUpateSetSqlStatement($runner, $class)
    {
        $result = $runner
            ->update('some_table')
            ->set('foo', new ExpressionRaw('id_user * 2'))
            ->join('users', 'u.id = id_user', 'u')
            ->where('id_user', $this->idJean)
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());
    }
}
