<?php

declare(strict_types=1);

namespace Goat\Runer\Tests\Query;

use Goat\Converter\DefaultConverter;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;

final class PgSQLCompositeTest extends DatabaseAwareQueryTest
{
    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(Runner $runner)
    {
        // Modified exemple from official PostgreSQL documentation.
        // https://www.postgresql.org/docs/9.2/rowtypes.html

        try {
            $runner->perform(<<<SQL
DROP TYPE IF EXISTS inventory_item;
SQL
            );

            // Added a nested array, good for testing.
            $runner->perform(<<<SQL
CREATE TYPE inventory_item AS (
    name text,
    tags text[],
    supplier_id integer,
    price numeric
);
SQL
            );
        } catch (\Throwable $e) {
            // @todo Find a better way to handle subsequent tests.
        }

        // Added a primary key to identify columns in tests.
        $runner->perform(<<<SQL
CREATE TEMPORARY TABLE on_hand (
    id int primary key,
    item inventory_item,
    count integer
);
SQL
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner)
    {
        // Exemple from official PostgreSQL documentation.
        // https://www.postgresql.org/docs/9.2/rowtypes.html
        $runner->perform(<<<SQL
INSERT INTO on_hand VALUES (1, ROW('fuzzy dice', '{"Fuzzy", "Dice"}', 42, 1.99), 1000);
SQL
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareConverter(DefaultConverter $converter)
    {
        /*
        $compositeValueConverter = new PgSQLCompositeToArrayConverter();
        $compositeValueConverter->addType('inventory_item', [
            'name' => 'text',
            'tags' => 'text[]',
            'supplier_id' => 'integer',
            'price' => 'numeric',
        ]);

        $converter->register($compositeValueConverter);
         */
    }

    /**
     * Test composite type selection
     *
     * @dataProvider getRunners
     */
    public function testSelectComposite(Runner $runner)
    {
        if ('pgsql' !== $runner->getDriverName()) {
            $this->markTestSkipped("This test is PostgreSQL only");
        }

        $this->prepare($runner);

        $result = $runner->execute("select * from on_hand where id = 1");

        foreach ($result as $row) {
            time();
        }
    }

    /**
     * Test composite type selection
     *
     * @dataProvider getRunners
     */
    public function testSelectCompositeRecursion(Runner $runner)
    {
        if ('pgsql' !== $runner->getDriverName()) {
            $this->markTestSkipped("This test is PostgreSQL only");
        }

        $this->markTestIncomplete("Implement me");
    }

    /**
     * Test composite type selection
     *
     * @dataProvider getRunners
     */
    public function testInsertComposite(Runner $runner)
    {
        if ('pgsql' !== $runner->getDriverName()) {
            $this->markTestSkipped("This test is PostgreSQL only");
        }

        $this->markTestIncomplete("Implement me");
    }

    /**
     * Test composite type selection
     *
     * @dataProvider getRunners
     */
    public function testSelectArray(Runner $runner)
    {
        if ('pgsql' !== $runner->getDriverName()) {
            $this->markTestSkipped("This test is PostgreSQL only");
        }

        $result = $runner->execute(<<<SQL
SELECT '{"Fuzzy", "Dice"}'::text[] AS "foo"
SQL
        );

        foreach ($result as $row) {
            $this->assertSame(['Fuzzy', 'Dice'], $row['foo']);
        }
    }

    /**
     * Test composite type selection
     *
     * @dataProvider getRunners
     */
    public function testSelectArrayRecursion(Runner $runner)
    {
        if ('pgsql' !== $runner->getDriverName()) {
            $this->markTestSkipped("This test is PostgreSQL only");
        }

        $result = $runner->execute(<<<SQL
SELECT '{{1, 2}, {7, 8}}'::int[][] AS "foo"
SQL
        );

        foreach ($result as $row) {
            $this->assertSame([[1, 2], [7, 8]], $row['foo']);
        }
    }

    /**
     * Test composite type selection
     *
     * @dataProvider getRunners
     */
    public function testInsertArray(Runner $runner)
    {
        if ('pgsql' !== $runner->getDriverName()) {
            $this->markTestSkipped("This test is PostgreSQL only");
        }

        $this->markTestIncomplete("Implement me");
    }
}
