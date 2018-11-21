<?php

declare(strict_types=1);

namespace Goat\Runer\Tests\Query;

use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;

class BinaryObjectTest extends DatabaseAwareQueryTest
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
     * @dataProvider getRunners
     */
    public function testInsertAndSelect(Runner $runner)
    {
        $this->prepare($runner);

        $runner
            ->getQueryBuilder()
            ->insertValues('storage')
            ->values([
                'foo' => "åß∂ƒ©˙∆˚¬…æ"
            ])
            ->execute()
        ;

        $value = $runner
            ->getQueryBuilder()
            ->select('storage')
            ->column('foo')
            ->execute()
            ->fetchField()
        ;

        if (\is_resource($value)) {
            $value = \stream_get_contents($value);
        }

        $this->assertSame("åß∂ƒ©˙∆˚¬…æ", $value);
    }
}
