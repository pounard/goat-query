<?php

declare(strict_types=1);

namespace Goat\Runer\Tests\Query;

use Goat\Runner\Runner;
use Goat\Tests\DriverTestCase;

class BinaryObjectTest extends DriverTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createTestSchema(Runner $runner)
    {
        if (false !== \stripos($runner->getDriverName(), 'pgsql')) {
            $runner->execute("
                create temporary table storage (
                    id serial primary key,
                    foo bytea
                )
            ");
        } else {
            $runner->execute("
                create temporary table storage (
                    id serial primary key,
                    foo blob
                )
            ");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner)
    {
    }

    /**
     * Very simple test
     *
     * @dataProvider driverDataSource
     */
    public function testInsertAndSelect($driverName, $class)
    {
        $runner
            ->insertValues('storage')
            ->values([
                'foo' => "åß∂ƒ©˙∆˚¬…æ"
            ])
            ->execute()
        ;

        $value = $runner
            ->select('storage')
            ->column('foo')
            ->execute()
            ->fetchField()
        ;

        $this->assertSame("åß∂ƒ©˙∆˚¬…æ", $value);
    }
}
