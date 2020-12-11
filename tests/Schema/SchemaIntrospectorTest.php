<?php

declare(strict_types=1);

namespace Goat\Schema\Tests;

use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;
use Goat\Schema\ObjectMetadata;

final class SchemaIntrospectorTest extends DatabaseAwareQueryTest
{
    use TestWithSchemaTrait;

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
        //self::assertSame(['position'], $table->getPrimaryKey());
        self::assertCount(14, $table->getColumnTypeMap());

        // @todo test (reverse) foreign keys
    }
}
