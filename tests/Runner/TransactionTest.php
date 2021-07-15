<?php

declare(strict_types=1);

namespace Goat\Runner\Tests;

use Goat\Driver\Error\TransactionError;
use Goat\Driver\Error\UniqueConstraintViolationError;
use Goat\Runner\Runner;
use Goat\Runner\Transaction;
use Goat\Runner\TransactionSavepoint;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

final class TransactionTest extends DatabaseAwareQueryTest
{
    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner, ?string $schema): void
    {
        $runner->execute("
            create temporary table transaction_test (
                id serial primary key,
                foo integer not null,
                bar varchar(255)
            )
        ");

        if ($runner->getPlatform()->supportsDeferingConstraints()) {
            $runner->execute("
                alter table transaction_test
                    add constraint transaction_test_foo
                    unique (foo)
                    deferrable
            ");

            $runner->execute("
                alter table transaction_test
                    add constraint transaction_test_bar
                    unique (bar)
                    deferrable
            ");
        } else {
            $runner->execute("
                alter table transaction_test
                    add constraint transaction_test_foo
                    unique (foo)
            ");

            $runner->execute("
                alter table transaction_test
                    add constraint transaction_test_bar
                    unique (bar)
            ");
        }

        $runner
            ->getQueryBuilder()
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([1, 'a'])
            ->values([2, 'b'])
            ->values([3, 'c'])
            ->execute()
        ;
    }

    /**
     * Normal working transaction
     *
     * @dataProvider runnerDataProvider
     */
    public function testTransaction(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $transaction = $runner->beginTransaction();

        $runner
            ->getQueryBuilder()
            ->insertValues('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([4, 'd'])
            ->execute()
        ;

        $transaction->commit();

        $result = $runner
            ->getQueryBuilder()
            ->select('transaction_test')
            ->orderBy('foo')
            ->execute()
        ;

        self::assertCount(4, $result);
        self::assertSame('a', $result->fetch()->get('bar'));
        self::assertSame('b', $result->fetch()->get('bar'));
        self::assertSame('c', $result->fetch()->get('bar'));
        self::assertSame('d', $result->fetch()->get('bar'));
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testNestedTransactionCreatesSavepoint(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        if (!$runner->getPlatform()->supportsTransactionSavepoints()) {
            self::markTestSkipped(\sprintf("Driver '%s' does not supports savepoints", $runner->getDriverName()));
        }

        $runner->getQueryBuilder()->delete('transaction_test')->execute();

        $transaction = $runner->beginTransaction();

        $runner
            ->getQueryBuilder()
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([789, 'f'])
            ->execute()
        ;

        $savepoint = $runner->beginTransaction();

        self::assertInstanceOf(TransactionSavepoint::class, $savepoint);
        self::assertTrue($savepoint->isNested());
        self::assertNotNull($savepoint->getSavepointName());

        $runner
            ->getQueryBuilder()
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([456, 'g'])
            ->execute()
        ;

        $transaction->commit();

        $result = $runner
            ->getQueryBuilder()
            ->select('transaction_test')
            ->orderBy('foo')
            ->execute()
        ;

        self::assertCount(2, $result);
        self::assertSame('g', $result->fetch()->get('bar'));
        self::assertSame('f', $result->fetch()->get('bar'));
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testNestedTransactionRollbackToSavepointTransparently(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        if (!$runner->getPlatform()->supportsTransactionSavepoints()) {
            self::markTestSkipped(\sprintf("Driver '%s' does not supports savepoints", $runner->getDriverName()));
        }

        $runner->getQueryBuilder()->delete('transaction_test')->execute();

        $transaction = $runner->beginTransaction();

        $runner
            ->getQueryBuilder()
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([789, 'f'])
            ->execute()
        ;

        $savepoint = $runner->beginTransaction();

        self::assertInstanceOf(TransactionSavepoint::class, $savepoint);
        self::assertTrue($savepoint->isNested());
        self::assertNotNull($savepoint->getSavepointName());

        $runner
            ->getQueryBuilder()
            ->insert('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([456, 'g'])
            ->execute()
        ;

        $savepoint->rollback();
        $transaction->commit();

        $result = $runner
            ->getQueryBuilder()
            ->select('transaction_test')
            ->orderBy('foo')
            ->execute()
        ;

        self::assertCount(1, $result);
        self::assertSame('f', $result->fetch()->get('bar'));
    }

    /**
     * Fail with immediate constraints (not deferred)
     *
     * @dataProvider runnerDataProvider
     */
    public function testImmediateTransactionFail(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $transaction = $runner
            ->beginTransaction()
            ->deferred() // Defer all
            ->immediate('transaction_test_bar')
        ;

        try {
            // This should pass, foo constraint it deferred;
            // if backend does not support defering, this will
            // fail anyway, but the rest of the test is still
            // valid
            $runner
                ->getQueryBuilder()
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->execute()
            ;

            // This should fail, bar constraint it immediate
            $runner
                ->getQueryBuilder()
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->execute()
            ;

            self::fail();

        } catch (TransactionError $e) {
            self::assertInstanceOf(UniqueConstraintViolationError::class, $e->getPrevious());
        } catch (\Throwable $e) {
            self::fail();
        } finally {
            if ($transaction->isStarted()) {
                $transaction->rollback();
            }
        }
    }

    /**
     * Fail with deferred constraints
     *
     * @dataProvider runnerDataProvider
     */
    public function testDeferredTransactionFail(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        if (!$runner->getPlatform()->supportsDeferingConstraints()) {
            self::markTestSkipped("driver does not support defering constraints");
        }

        $transaction = $runner
            ->beginTransaction()
            ->immediate() // Immediate all
            ->deferred('transaction_test_foo')
        ;

        try {

            // This should pass, foo constraint it deferred
            $runner
                ->getQueryBuilder()
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->execute()
            ;

            // This should fail, bar constraint it immediate
            $runner
                ->getQueryBuilder()
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->execute()
            ;

            self::fail();

        } catch (TransactionError $e) {
            self::assertInstanceOf(UniqueConstraintViolationError::class, $e->getPrevious());
        } catch (\Throwable $e) {
            self::fail();
        } finally {
            if ($transaction->isStarted()) {
                $transaction->rollback();
            }
        }

        self::assertTrue(true);
    }

    /**
     * Fail with ALL constraints deferred
     *
     * @dataProvider runnerDataProvider
     */
    public function testDeferredAllTransactionFail(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        if (!$runner->getPlatform()->supportsDeferingConstraints()) {
            self::markTestSkipped("driver does not support defering constraints");
        }

        $transaction = $runner
            ->beginTransaction()
            ->deferred()
        ;

        try {

            // This should pass, all are deferred
            $runner
                ->getQueryBuilder()
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->execute()
            ;

            // This should pass, all are deferred
            $runner
                ->getQueryBuilder()
                ->insert('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->execute()
            ;

            $transaction->commit();

        } catch (TransactionError $e) {
            self::assertInstanceOf(UniqueConstraintViolationError::class, $e->getPrevious());
        } catch (\Throwable $e) {
            self::fail();
        } finally {
            if ($transaction->isStarted()) {
                $transaction->rollback();
            }
        }

        self::assertTrue(true);
    }

    /**
     * Tests that rollback works
     *
     * @dataProvider runnerDataProvider
     */
    public function testTransactionRollback(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $transaction = $runner->beginTransaction();

        $runner
            ->getQueryBuilder()
            ->insertValues('transaction_test')
            ->columns(['foo', 'bar'])
            ->values([4, 'd'])
            ->execute()
        ;

        $transaction->rollback();

        $result = $runner
            ->getQueryBuilder()
            ->select('transaction_test')
            ->execute()
        ;

        self::assertCount(3, $result);
    }

    /**
     * Test that fetching a pending transaction is disallowed
     *
     * @dataProvider runnerDataProvider
     */
    public function testPendingAllowed(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $transaction = $runner->beginTransaction();

        // Fetch another transaction, it should fail
        try {
            $runner->beginTransaction(Transaction::REPEATABLE_READ, false);
            self::fail();
        } catch (TransactionError $e) {
        }

        // Fetch another transaction, it should NOT fail
        $t3 = $runner->beginTransaction(Transaction::REPEATABLE_READ, true);
        // @todo temporary deactivating this test since that the profiling
        //   transaction makes it harder
        //self::assertSame($t3, $transaction);
        self::assertTrue($t3->isStarted());

        // Force rollback of the second, ensure previous is stopped too
        $t3->rollback();
        self::assertFalse($t3->isStarted());
        // Still true, because we acquired a savepoint
        self::assertTrue($transaction->isStarted());

        $transaction->rollback();
        self::assertFalse($transaction->isStarted());
    }

    /**
     * Test the savepoint feature
     *
     * @dataProvider runnerDataProvider
     */
    public function testTransactionSavepoint(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

        $transaction = $runner->beginTransaction();

        $runner
            ->getQueryBuilder()
            ->update('transaction_test')
            ->set('bar', 'z')
            ->where('foo', 1)
            ->execute()
        ;

        $transaction->savepoint('bouyaya');

        $runner
            ->getQueryBuilder()
            ->update('transaction_test')
            ->set('bar', 'y')
            ->where('foo', 2)
            ->execute()
        ;

        $transaction->rollbackToSavepoint('bouyaya');
        $transaction->commit();

        $oneBar = $runner
            ->getQueryBuilder()
            ->select('transaction_test')
            ->column('bar')
            ->where('foo', 1)
            ->execute()
            ->fetchField()
        ;
        // This should have pass since it's before the savepoint
        self::assertSame('z', $oneBar);

        $twoBar = $runner
            ->getQueryBuilder()
            ->select('transaction_test')
            ->column('bar')
            ->where('foo', 2)
            ->execute()
            ->fetchField()
        ;
        // This should not have pass thanks to savepoint
        self::assertSame('b', $twoBar);
    }
}
