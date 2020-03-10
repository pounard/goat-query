<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\InsertQueryQuery;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use PHPUnit\Framework\TestCase;

final class InsertQueryUnitTest extends TestCase
{
    use BuilderTestTrait;

    public function testQueryInsertBasics(): void
    {
        $insert = new InsertQueryQuery('some_table');
        $insert->columns(['pif', 'paf']);

        $select = new SelectQuery('other_table');
        $select->columns(['foo', 'bar']);
        $select->condition('baz', true);

        $insert->query($select);

        self::assertSameSql(
            'insert into "some_table" ("pif", "paf") select "foo", "bar" from "other_table" where "baz" = ?',
            self::createStandardSqlWriter()->format($insert)
        );
    }

    public function testInsertQueryWithoutQueryFails(): void
    {
        $insert = new InsertQueryQuery('some_table');
        $insert->columns(['pif', 'paf']);

        self::expectException(QueryError::class);
        self::createStandardSqlWriter()->format($insert);
    }
}
