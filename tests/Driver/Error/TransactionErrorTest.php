<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query;

use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;
use Goat\Runner\Transaction;

class TransactionErrorTest extends DatabaseAwareQueryTest
{
    /**
     * Scenario here comes from official PostgreSQL documentation.
     *
     * @link https://www.postgresql.org/docs/10/transaction-iso.html#XACT-SERIALIZABLE
     *
     * @dataProvider runnerDataProvider
     */
    public function testSerializationError1(TestDriverFactory $factory)
    {
        $runner1 = $factory->getRunner();

        if (!$runner1->getPlatform()->supportsSchema()) {
            self::markTestSkipped("This test requires a schema.");
        }
        self::markTestIncomplete("Why does the heck it does not fail?");

        $runner2 = $factory->getRunner();

        $runner1->execute(
            <<<SQL
            DROP TABLE IF EXISTS public.test_transaction_1
            SQL
        );

        $runner1->execute(
            <<<SQL
            CREATE TABLE IF NOT EXISTS public.test_transaction_1 (class int, value int)
            SQL
        );

        $runner1->execute(
            <<<SQL
            INSERT INTO public.test_transaction_1 (class, value)
            VALUES (
                1, 10
            ), (
                1, 20
            ), (
                2, 100
            ), (
                2, 200
            )
            SQL
        );

        // Default level is REPEATABLE READ.
        $transaction1 = $runner1->beginTransaction(Transaction::SERIALIZABLE);
        $transaction2 = $runner2->beginTransaction(Transaction::SERIALIZABLE);

        $runner1->execute(
            <<<SQL
            SELECT SUM(value) FROM public.test_transaction_1 WHERE class = 1;
            SQL
        );

        $runner1->execute(
            <<<SQL
            INSERT INTO public.test_transaction_1 (class, value) VALUES (2, 30)
            SQL
        );

        $runner2->execute(
            <<<SQL
            SELECT SUM(value) FROM public.test_transaction_1 WHERE class = 2;
            SQL
        );

        $runner2->execute(
            <<<SQL
            INSERT INTO public.test_transaction_1 (class, value) VALUES (1, 300)
            SQL
        );

        $transaction1->commit();
        $transaction2->commit();
    }

    /**
     * Basic scenario.
     *
     * @dataProvider runnerDataProvider
     */
    public function testSerializationError2(TestDriverFactory $factory)
    {
        $runner1 = $factory->getRunner();

        if (!$runner1->getPlatform()->supportsSchema()) {
            self::markTestSkipped("This test requires a schema.");
        }
        self::markTestIncomplete("This test requires that we send a query batch asynchronously in the second transaction.");

        $runner2 = $factory->getRunner();

        $runner1->execute(
            <<<SQL
            DROP TABLE IF EXISTS public.test_transaction_2
            SQL
        );

        $runner1->execute(
            <<<SQL
            CREATE TABLE IF NOT EXISTS public.test_transaction_2 (id int PRIMARY KEY)
            SQL
        );

        // Default level is REPEATABLE READ.
        $transaction1 = $runner1->beginTransaction(Transaction::SERIALIZABLE);
        $transaction2 = $runner2->beginTransaction(Transaction::SERIALIZABLE);

        $runner1->execute(
            <<<SQL
            INSERT INTO public.test_transaction_2 (id) VALUES (1)
            SQL
        );

        $runner2->execute(
            <<<SQL
            INSERT INTO public.test_transaction_2 (id) VALUES (1)
            SQL
        );

        $transaction1->commit();
        $transaction2->commit();
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testDeadlockError(TestDriverFactory $factory)
    {
        self::markTestIncomplete("Untested yet.");
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testWaitTimeoutError(TestDriverFactory $factory)
    {
        self::markTestIncomplete("Untested yet.");
    }
}
