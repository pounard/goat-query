<?php

declare(strict_types=1);

namespace Goat\Query\Tests;

use Goat\Query\InsertValuesQuery;
use PHPUnit\Framework\TestCase;

final class InsertValuesUnitTest extends TestCase
{
    use BuilderTestTrait;

    public function testInsertValuesUsesColumnsFromFirst(): void
    {
        $insert = new InsertValuesQuery('some_table');
        $insert->values([
            'foo' => 'bar',
            'int' => 3,
        ]);

        self::assertSameSql(
            'insert into "some_table" ("foo", "int") values (?, ?)',
            self::createStandardSqlWriter()->format($insert)
        );
    }

    public function testInsertValuesIngoreKeysFromNext(): void
    {
        $insert = new InsertValuesQuery('some_table');
        $insert->values([
            'foo' => 'bar',
            'int' => 3,
        ]);
        $insert->values([
            'pif' => 'pouf',
            'paf' => 3,
        ]);

        self::assertSameSql(
            'insert into "some_table" ("foo", "int") values (?, ?), (?, ?)',
            self::createStandardSqlWriter()->format($insert)
        );
    }

    public function testInsertValuesWithColumnCall(): void
    {
        $insert = new InsertValuesQuery('some_table');
        $insert->columns(['a', 'b']);
        $insert->values([
            'foo' => 'bar',
            'int' => 3,
        ]);
        $insert->values([
            'pouf',
            3,
        ]);

        self::assertSameSql(
            'insert into "some_table" ("a", "b") values (?, ?), (?, ?)',
            self::createStandardSqlWriter()->format($insert)
        );
    }
}
