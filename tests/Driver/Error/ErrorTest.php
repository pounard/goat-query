<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query;

use Goat\Driver\Error;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

class ErrorTest extends DatabaseAwareQueryTest
{
    /**
     * @dataProvider runnerDataProvider
     */
    public function testRelationDoesNotExist(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        self::expectException(Error\RelationDoesNotExistError::class);

        $runner->execute(
            <<<SQL
            SELECT 1 FROM non_existing_table;
            SQL
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testNotNullConstraintViolation(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $runner->execute(
            <<<SQL
            CREATE TEMPORARY TABLE test_exception (foo int NOT NULL, bar int)
            SQL
        );

        self::expectException(Error\NotNullConstraintViolationError::class);

        $runner->execute(
            <<<SQL
            INSERT INTO test_exception (bar) VALUES (1)
            SQL
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testForeignKeyConstraintViolation(TestDriverFactory $factory)
    {
        if (false !== \strpos($factory->getDriverName(), 'mysql')) {
            self::markTestSkipped("MySQL does not seem to accept FOREIGN KEY constraints on TEMPORARY TABLE");
        }

        $runner = $factory->getRunner();

        $runner->execute(
            <<<SQL
            CREATE TEMPORARY TABLE test_exception_1 (foo int PRIMARY KEY)
            SQL
        );

        $runner->execute(
            <<<SQL
            CREATE TEMPORARY TABLE test_exception_2 (
                bar int,
                FOREIGN KEY (bar) REFERENCES test_exception_1 (foo)
            )
            SQL
        );

        self::expectException(Error\ForeignKeyConstraintViolationError::class);

        $runner->execute(
            <<<SQL
            INSERT INTO test_exception_2 (bar) VALUES (1)
            SQL
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testUniqueConstraintViolation(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $runner->execute(
            <<<SQL
            CREATE TEMPORARY TABLE test_exception (foo int UNIQUE)
            SQL
        );

        $runner->execute(
            <<<SQL
            INSERT INTO test_exception (foo) VALUES (1)
            SQL
        );

        self::expectException(Error\UniqueConstraintViolationError::class);

        $runner->execute(
            <<<SQL
            INSERT INTO test_exception (foo) VALUES (1)
            SQL
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testAmbiguousIdentifier(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $runner->execute(
            <<<SQL
            CREATE TEMPORARY TABLE test_exception_1 (foo int UNIQUE)
            SQL
        );

        $runner->execute(
            <<<SQL
            CREATE TEMPORARY TABLE test_exception_2 (foo int UNIQUE)
            SQL
        );

        self::expectException(Error\AmbiguousIdentifierError::class);

        $runner->execute(
            <<<SQL
            SELECT foo
            FROM test_exception_1, test_exception_2
            SQL
        );
    }
}
