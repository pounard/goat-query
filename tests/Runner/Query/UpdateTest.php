<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query;

use Goat\Query\ExpressionColumn;
use Goat\Query\ExpressionRaw;
use Goat\Query\Where;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

class UpdateTest extends DatabaseAwareQueryTest
{
    const ID_ADMIN = 1;
    const ID_JEAN = 2;

    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner, ?string $schema): void
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
                id integer primary key,
                name varchar(255)
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
     * Update using simple WHERE conditions
     *
     * @dataProvider runnerDataProvider
     */
    public function testUpdateWhere(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $result = $runner
            ->getQueryBuilder()
            ->update('some_table')
            ->condition('foo', 42)
            ->set('foo', 43)
            ->execute()
        ;

        $this->assertSame(1, $result->countRows());

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table')
            ->condition('foo', 43)
            ->execute()
        ;

        $this->assertSame(1, $result->countRows());
        $this->assertSame('a', $result->fetch()['bar']);

        $query = $runner
            ->getQueryBuilder()
            ->update('some_table', 'trout')
        ;
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
     * @dataProvider runnerDataProvider
     */
    public function testUpdateJoin(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $result = $runner
            ->getQueryBuilder()
            ->update('some_table', 't')
            ->set('foo', 127)
            ->join('users', "u.id = t.id_user", 'u')
            ->condition('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $runner
            ->getQueryBuilder()
            ->select('some_table', 'roger')
            ->join('users', 'john.id = roger.id_user', 'john')
            ->condition('john.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame(127, $row['foo']);
        }

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table')
            ->condition('foo', 127)
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
    }

    /**
     * Update using a IN (SELECT ...)
     *
     * @dataProvider runnerDataProvider
     */
    public function testUpdateWhereIn(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $selectInQuery = $runner
            ->getQueryBuilder()
            ->select('users')
            ->column('id')
            ->condition('name', 'admin')
        ;

        $result = $runner
            ->getQueryBuilder()
            ->update('some_table', 't')
            ->set('foo', 127)
            ->condition('t.id_user', $selectInQuery)
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $runner
            ->getQueryBuilder()
            ->select('some_table', 'roger')
            ->join('users', 'john.id = roger.id_user', 'john')
            ->condition('john.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
        foreach ($result as $row) {
            $this->assertSame(127, $row['foo']);
        }

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table')
            ->condition('foo', 127)
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());
    }

    /**
     * Update RETURNING
     *
     * @dataProvider runnerDataProvider
     */
    public function testUpdateReturning(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        if (!$runner->getPlatform()->supportsReturning()) {
            $this->markTestSkipped("driver does not support RETURNING");
        }

        $result = $runner
            ->getQueryBuilder()
            ->update('some_table', 't')
            ->set('foo', 127)
            ->join('users', "u.id = t.id_user", 'u')
            ->condition('u.name', 'admin')
            ->returning('*')
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
     * @dataProvider runnerDataProvider
     */
    public function testUpdateSetExpressionColumn(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $result = $runner
            ->getQueryBuilder()
            ->update('some_table', 't')
            ->set('foo', new ExpressionColumn('u.id'))
            ->join('users', "u.id = t.id_user", 'u')
            ->condition('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $runner
            ->getQueryBuilder()
            ->select('some_table', 't')
            ->columns(['t.foo', 't.id_user'])
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.name', 'admin')
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
     * @dataProvider runnerDataProvider
     */
    public function testUpdateSetExpressionRaw(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $result = $runner
            ->getQueryBuilder()
            ->update('some_table', 't')
            ->set('foo', new ExpressionRaw('u.id'))
            ->join('users', "u.id = t.id_user", 'u')
            ->condition('u.name', 'admin')
            ->execute()
        ;

        $this->assertSame(3, $result->countRows());

        // All code below is just consistency checks
        $result = $runner
            ->getQueryBuilder()
            ->select('some_table', 't')
            ->columns(['t.foo', 't.id_user'])
            ->join('users', 'u.id = t.id_user', 'u')
            ->condition('u.name', 'admin')
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
     * @dataProvider runnerDataProvider
     */
    public function testUpdateSetSelectQuery(TestDriverFactory $factory)
    {
        self::markTestIncomplete("Investigate if this needs to be supported or not.");

        $runner = $factory->getRunner();

        $selectValueQuery = $runner
            ->getQueryBuilder()
            ->select('users', 'z')
            ->columnExpression('z.id + 7')
            ->expression('z.id = id_user')
        ;

        $result = $runner
            ->getQueryBuilder()
            ->update('some_table')
            ->set('foo', $selectValueQuery)
            ->condition('id_user', self::ID_JEAN)
            ->execute()
        ;

        $this->assertSame(2, $result->countRows());

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table')
            ->condition('id_user', self::ID_JEAN)
            ->execute()
        ;

        foreach ($result as $row) {
            $this->assertSame($row['id_user'] + 7, $row['foo']);
        }

        $result = $runner
            ->getQueryBuilder()
            ->select('some_table')
            ->condition('id_user', self::ID_ADMIN)
            ->execute()
        ;

        foreach ($result as $row) {
            $this->assertNotSame($row['id_user'] + 7, $row['foo']);
        }
    }

    /**
     * Update by using SET column = some_statement()
     *
     * @dataProvider runnerDataProvider
     */
    public function testUpdateSetSqlStatement(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $affectedRows = $runner
            ->getQueryBuilder()
            ->update('some_table')
            ->set('foo', new ExpressionRaw('id_user * 2'))
            ->join('users', 'u.id = id_user', 'u')
            ->condition('id_user', self::ID_JEAN)
            ->perform()
        ;

        $this->assertSame(2, $affectedRows);
    }
}
