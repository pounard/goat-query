<?php

declare(strict_types=1);

namespace Goat\Runner\Tests;

use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;
use Goat\Schema\ObjectMetadata;

final class SchemaIntrospectorTest extends DatabaseAwareQueryTest
{
    private function createInitialSchema(Runner $runner): void
    {
        $driver = $runner->getDriverName();

        if (false !== \strpos($driver, 'pgsql')) {
            $this->createInitialSchemaFromFile($runner, __DIR__ . '/Schema/schema.pgsql.sql');
        }

        if (false !== \strpos($driver, 'mysql')) {
            self::markTestSkipped("Schema introspector is not implemented in MySQL yet.");
        }
    }

    private function createInitialSchemaFromFile(Runner $runner, string $filename): void
    {
        $statements = \array_filter(
            \array_map(
                // Do not execute void (empty lines).
                fn ($text) => \trim($text),
                // Hopefully, we don't have any dangling ';' in escaped text.
                \explode(
                    ';',
                    \file_get_contents($filename)
                )
            )
        );

        foreach ($statements as $statement) {
            $runner->execute($statement);
        }
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testListDatabases(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();
        $this->createInitialSchema($runner);
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        self::assertContains(
            $runner->getSessionConfiguration()->getDatabase(),
            $schemaIntrospector->listDatabases()
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testListSchemas(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();
        $this->createInitialSchema($runner);
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        self::assertContains(
            $factory->getSchema(),
            $schemaIntrospector->listSchemas()
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testListTables(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();
        $this->createInitialSchema($runner);
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        self::assertSame(
            [
                'event_default',
                'event_index',
                'message_broker',
                'message_broker_dead_letters',
            ],
            $schemaIntrospector->listTables(
                $factory->getSchema()
            )
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testTableExists(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();
        $this->createInitialSchema($runner);
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        self::assertTrue($schemaIntrospector->tableExists($factory->getSchema(), 'event_index'));
        self::assertFalse($schemaIntrospector->tableExists($factory->getSchema(), 'foobar'));
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testFetchTableMetadata(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();
        $this->createInitialSchema($runner);
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        $table = $schemaIntrospector->fetchTableMetadata($factory->getSchema(), 'event_default');

        self::assertSame($runner->getSessionConfiguration()->getDatabase(), $table->getDatabase());
        self::assertSame($factory->getSchema(), $table->getSchema());
        self::assertSame('event_default', $table->getName());
        self::assertSame(ObjectMetadata::OBJECT_TYPE_TABLE, $table->getObjectType());
        self::assertSame(['position'], $table->getPrimaryKey());
        self::assertCount(14, $table->getColumnTypeMap());
    }
}
