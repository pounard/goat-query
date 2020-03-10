<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query;

use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;

class BinaryObjectTest extends DatabaseAwareQueryTest
{
    /**
     * {@inheritdoc}
     */
    protected function createTestData(Runner $runner, ?string $schema): void
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

    /** @dataProvider runnerDataProvider */
    public function testInsertAndSelect(TestDriverFactory $factory)
    {
        $runner = $factory->getRunner();

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

        // @todo Should blog be object instances with a getContents() instead?
        $escaper = $runner->getPlatform()->getEscaper();
        $this->assertSame("åß∂ƒ©˙∆˚¬…æ", $escaper->unescapeBlob($value));
    }
}
