<?php

declare(strict_types=1);

namespace Goat\Runner\Tests;

use Goat\Runner\Runner;
use Goat\Runner\Transaction;
use Goat\Runner\TransactionError;
use Goat\Runner\TransactionFailedError;
use Goat\Runner\Testing\DatabaseAwareQueryTest;

class TransactionTest extends DatabaseAwareQueryTest
{
    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(Runner $runner)
    {
        $runner->execute("
            create temporary table transaction_test (
                id serial primary key,
                foo integer not null,
                bar varchar(255)
            )
        ");

        if ($runner->supportsDeferingConstraints()) {
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
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner)
    {
        $runner
            ->getQueryBuilder()
            ->insertValues('transaction_test')
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
     * @dataProvider getRunners
     */
    public function testTransaction(Runner $runner)
    {
        $this->prepare($runner);

        $transaction = $runner->startTransaction();
        $transaction->start();

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

        $this->assertCount(4, $result);
        $this->assertSame('a', $result->fetch()['bar']);
        $this->assertSame('b', $result->fetch()['bar']);
        $this->assertSame('c', $result->fetch()['bar']);
        $this->assertSame('d', $result->fetch()['bar']);
    }

    /**
     * Fail with immediate constraints (not deferred)
     *
     * @dataProvider getRunners
     */
    public function testImmediateTransactionFail(Runner $runner)
    {
        $this->prepare($runner);

        $transaction = $runner
            ->startTransaction()
            ->start()
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
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->execute()
            ;

            // This should fail, bar constraint it immediate
            $runner
                ->getQueryBuilder()
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->execute()
            ;

            $this->fail();

        } catch (TransactionFailedError $e) {
            // This must not happen because of immediate constraints
            $this->fail();
        } catch (\Exception $e) {
            // This should happen instead, arbitrary SQL error
            $transaction->rollback();
        } finally {
            if ($transaction->isStarted()) {
                $transaction->rollback();
            }
        }

        $this->assertTrue(true);
    }

    /**
     * Fail with deferred constraints
     *
     * @dataProvider getRunners
     */
    public function testDeferredTransactionFail(Runner $runner)
    {
        $this->prepare($runner);

        if (!$runner->supportsDeferingConstraints()) {
            $this->markTestSkipped("driver does not support defering constraints");
        }

        $transaction = $runner
            ->startTransaction()
            ->start()
            ->immediate() // Immediate all
            ->deferred('transaction_test_foo')
        ;

        try {

            // This should pass, foo constraint it deferred
            $runner
                ->getQueryBuilder()
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->execute()
            ;

            // This should fail, bar constraint it immediate
            $runner
                ->getQueryBuilder()
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->execute()
            ;

            $this->fail();

        } catch (TransactionFailedError $e) {
            // This must not happen because of immediate constraints
            $this->fail();
        } catch (\Exception $e) {
            // This should happen instead, arbitrary SQL error
            $transaction->rollback();
        } finally {
            if ($transaction->isStarted()) {
                $transaction->rollback();
            }
        }

        $this->assertTrue(true);
    }

    /**
     * Fail with ALL constraints deferred
     *
     * @dataProvider getRunners
     */
    public function testDeferredAllTransactionFail(Runner $runner)
    {
        $this->prepare($runner);

        if (!$runner->supportsDeferingConstraints()) {
            $this->markTestSkipped("driver does not support defering constraints");
        }

        $transaction = $runner
            ->startTransaction()
            ->start()
            ->deferred()
        ;

        try {

            // This should pass, all are deferred
            $runner
                ->getQueryBuilder()
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([2, 'd'])
                ->execute()
            ;

            // This should pass, all are deferred
            $runner
                ->getQueryBuilder()
                ->insertValues('transaction_test')
                ->columns(['foo', 'bar'])
                ->values([5, 'b'])
                ->execute()
            ;

            $transaction->commit();

        } catch (TransactionFailedError $e) {
            // This is what should happen, error at commit time
            $transaction->rollback();
        } catch (\Exception $e) {
            // All constraints are deffered, we should not experience arbitrary
            // SQL errors at insert time
            $this->fail();
        } finally {
            if ($transaction->isStarted()) {
                $transaction->rollback();
            }
        }

        $this->assertTrue(true);
    }

    /**
     * Tests that rollback works
     *
     * @dataProvider getRunners
     */
    public function testTransactionRollback(Runner $runner)
    {
        $this->prepare($runner);

        $transaction = $runner->startTransaction();
        $transaction->start();

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

        $this->assertCount(3, $result);
    }

    /**
     * Test that fetching a pending transaction is disallowed
     *
     * @dataProvider getRunners
     */
    public function testPendingAllowed(Runner $runner)
    {
        $this->prepare($runner);

        $transaction = $runner->startTransaction();
        $transaction->start();

        // Fetch another transaction, it should fail
        try {
            $runner->startTransaction();
            $this->fail();
        } catch (TransactionError $e) {
        }

        // Fetch another transaction, it should NOT fail
        $t3 = $runner->startTransaction(Transaction::REPEATABLE_READ, true);
        // @todo temporary deactivating this test since that the profiling
        //   transaction makes it harder
        //$this->assertSame($t3, $transaction);
        $this->assertTrue($t3->isStarted());

        // Force rollback of the second, ensure previous is stopped too
        $t3->rollback();
        $this->assertFalse($transaction->isStarted());
    }

    /**
     * Test the savepoint feature
     *
     * @dataProvider getRunners
     */
    public function testTransactionSavepoint(Runner $runner)
    {
        $this->prepare($runner);

        $transaction = $runner->startTransaction();
        $transaction->start();

        $runner
            ->getQueryBuilder()
            ->update('transaction_test')
            ->set('bar', 'z')
            ->condition('foo', 1)
            ->execute()
        ;

        $transaction->savepoint('bouyaya');

        $runner
            ->getQueryBuilder()
            ->update('transaction_test')
            ->set('bar', 'y')
            ->condition('foo', 2)
            ->execute()
        ;

        $transaction->rollbackToSavepoint('bouyaya');
        $transaction->commit();

        $oneBar = $runner
            ->getQueryBuilder()
            ->select('transaction_test')
            ->column('bar')
            ->condition('foo', 1)
            ->execute()
            ->fetchField()
        ;
        // This should have pass since it's before the savepoint
        $this->assertSame('z', $oneBar);

        $twoBar = $runner
            ->getQueryBuilder()
            ->select('transaction_test')
            ->column('bar')
            ->condition('foo', 2)
            ->execute()
            ->fetchField()
        ;
        // This should not have pass thanks to savepoint
        $this->assertSame('b', $twoBar);
    }
}
