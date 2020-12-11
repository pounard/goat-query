<?php

declare(strict_types=1);

namespace Goat\Schema\Tests\Browser;

use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\TestDriverFactory;
use Goat\Schema\Browser\SchemaBrowser;
use Goat\Schema\Tests\TestWithSchemaTrait;
use Goat\Schema\Analytics\PgSQLAnalyticsVisitor;

final class SchemaBrowserTest extends DatabaseAwareQueryTest
{
    use TestWithSchemaTrait;

    /**
     * @dataProvider runnerDataProvider
     */
    public function testAnalytics(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();
        $this->createInitialSchema($runner);
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        $visitor = new PgSQLAnalyticsVisitor($runner);

        (new SchemaBrowser($schemaIntrospector))
            ->visitor($visitor)
            ->browseTable($factory->getSchema(), 'event_index', SchemaBrowser::MODE_RELATION_BOTH)
        ;
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testBrowseTable(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();
        $this->createInitialSchema($runner);
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        $visitor = new OutputVisitor();

        (new SchemaBrowser($schemaIntrospector))
            ->visitor($visitor)
            ->browseTable($factory->getSchema(), 'event_index', SchemaBrowser::MODE_RELATION_BOTH)
        ;

        self::assertSame(
            <<<TXT
            2; event_index; TABLE; event_index
            3; event_index; PRIMARY KEY; event_index_pkey ON event_index (aggregate_id)
            3; event_index; COLUMN; event_index.aggregate_id
            3; event_index; COLUMN; event_index.aggregate_type
            3; event_index; COLUMN; event_index.aggregate_root
            3; event_index; COLUMN; event_index.namespace
            3; event_index; COLUMN; event_index.created_at
            3; event_index; FOREIGN KEY; event_index_aggregate_root_fkey ON event_index (aggregate_root) REFERENCES event_index (aggregate_id)
            3; event_index; REVERSE FOREIGN KEY; event_default_aggregate_id_fkey ON event_default (aggregate_id) REFERENCES event_index (aggregate_id)
            4; event_index; TABLE; event_default
            5; event_index; PRIMARY KEY; event_default_pkey ON event_default (position)
            5; event_index; COLUMN; event_default.position
            5; event_index; COLUMN; event_default.aggregate_id
            5; event_index; COLUMN; event_default.revision
            5; event_index; COLUMN; event_default.created_at
            5; event_index; COLUMN; event_default.valid_at
            5; event_index; COLUMN; event_default.name
            5; event_index; COLUMN; event_default.properties
            5; event_index; COLUMN; event_default.data
            5; event_index; COLUMN; event_default.has_failed
            5; event_index; COLUMN; event_default.error_code
            5; event_index; COLUMN; event_default.error_message
            5; event_index; COLUMN; event_default.error_trace
            5; event_index; COLUMN; event_default.source_channel
            5; event_index; COLUMN; event_default.source_owner
            5; event_index; FOREIGN KEY; event_default_aggregate_id_fkey ON event_default (aggregate_id) REFERENCES event_index (aggregate_id)
            TXT,
            \trim($visitor->getOutput())
        );
    }

    /**
     * @dataProvider runnerDataProvider
     */
    public function testBrowseSchema(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();
        $this->createInitialSchema($runner);
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        $visitor = new OutputVisitor();

        (new SchemaBrowser($schemaIntrospector))
            ->visitor($visitor)
            ->browseSchema($factory->getSchema(), SchemaBrowser::MODE_RELATION_BOTH)
        ;

        self::assertSame(
            <<<TXT
            2; event_default; TABLE; event_default
            3; event_default; PRIMARY KEY; event_default_pkey ON event_default (position)
            3; event_default; COLUMN; event_default.position
            3; event_default; COLUMN; event_default.aggregate_id
            3; event_default; COLUMN; event_default.revision
            3; event_default; COLUMN; event_default.created_at
            3; event_default; COLUMN; event_default.valid_at
            3; event_default; COLUMN; event_default.name
            3; event_default; COLUMN; event_default.properties
            3; event_default; COLUMN; event_default.data
            3; event_default; COLUMN; event_default.has_failed
            3; event_default; COLUMN; event_default.error_code
            3; event_default; COLUMN; event_default.error_message
            3; event_default; COLUMN; event_default.error_trace
            3; event_default; COLUMN; event_default.source_channel
            3; event_default; COLUMN; event_default.source_owner
            3; event_default; FOREIGN KEY; event_default_aggregate_id_fkey ON event_default (aggregate_id) REFERENCES event_index (aggregate_id)
            4; event_default; TABLE; event_index
            5; event_default; PRIMARY KEY; event_index_pkey ON event_index (aggregate_id)
            5; event_default; COLUMN; event_index.aggregate_id
            5; event_default; COLUMN; event_index.aggregate_type
            5; event_default; COLUMN; event_index.aggregate_root
            5; event_default; COLUMN; event_index.namespace
            5; event_default; COLUMN; event_index.created_at
            5; event_default; FOREIGN KEY; event_index_aggregate_root_fkey ON event_index (aggregate_root) REFERENCES event_index (aggregate_id)
            5; event_default; REVERSE FOREIGN KEY; event_default_aggregate_id_fkey ON event_default (aggregate_id) REFERENCES event_index (aggregate_id)
            2; message_broker; TABLE; message_broker
            3; message_broker; PRIMARY KEY; message_broker_pkey ON message_broker (serial)
            3; message_broker; COLUMN; message_broker.id
            3; message_broker; COLUMN; message_broker.serial
            3; message_broker; COLUMN; message_broker.queue
            3; message_broker; COLUMN; message_broker.created_at
            3; message_broker; COLUMN; message_broker.consumed_at
            3; message_broker; COLUMN; message_broker.has_failed
            3; message_broker; COLUMN; message_broker.headers
            3; message_broker; COLUMN; message_broker.type
            3; message_broker; COLUMN; message_broker.content_type
            3; message_broker; COLUMN; message_broker.body
            3; message_broker; COLUMN; message_broker.error_code
            3; message_broker; COLUMN; message_broker.error_message
            3; message_broker; COLUMN; message_broker.error_trace
            3; message_broker; COLUMN; message_broker.retry_count
            3; message_broker; COLUMN; message_broker.retry_at
            2; message_broker_dead_letters; TABLE; message_broker_dead_letters
            3; message_broker_dead_letters; PRIMARY KEY; message_broker_dead_letters_pkey ON message_broker_dead_letters (id)
            3; message_broker_dead_letters; COLUMN; message_broker_dead_letters.id
            3; message_broker_dead_letters; COLUMN; message_broker_dead_letters.serial
            3; message_broker_dead_letters; COLUMN; message_broker_dead_letters.queue
            3; message_broker_dead_letters; COLUMN; message_broker_dead_letters.created_at
            3; message_broker_dead_letters; COLUMN; message_broker_dead_letters.consumed_at
            3; message_broker_dead_letters; COLUMN; message_broker_dead_letters.headers
            3; message_broker_dead_letters; COLUMN; message_broker_dead_letters.type
            3; message_broker_dead_letters; COLUMN; message_broker_dead_letters.content_type
            3; message_broker_dead_letters; COLUMN; message_broker_dead_letters.body
            3; message_broker_dead_letters; COLUMN; message_broker_dead_letters.error_code
            3; message_broker_dead_letters; COLUMN; message_broker_dead_letters.error_message
            3; message_broker_dead_letters; COLUMN; message_broker_dead_letters.error_trace
            TXT,
            \trim($visitor->getOutput())
        );
    }

    /**
     * @dataProvider runnerDataProvider
     *
    public function testBrowseWithoutCircularDependencyBreak(TestDriverFactory $factory): void
    {
        $runner = $factory->getRunner();
        $this->createInitialSchema($runner);
        $schemaIntrospector = $runner->getPlatform()->createSchemaIntrospector($runner);

        $visitor = new OutputVisitor();

        (new SchemaBrowser($schemaIntrospector))
            ->visitor($visitor)
            ->browseSchema($factory->getSchema(), SchemaBrowser::MODE_RELATION_BOTH)
        ;

        self::assertSame(
            <<<TXT
            2; event_default; TABLE; event_default
            3; event_default; PRIMARY KEY; event_default_pkey ON event_default (position)
            3; event_default; COLUMN; position
            3; event_default; COLUMN; aggregate_id
            3; event_default; COLUMN; revision
            3; event_default; COLUMN; created_at
            3; event_default; COLUMN; valid_at
            3; event_default; COLUMN; name
            3; event_default; COLUMN; properties
            3; event_default; COLUMN; data
            3; event_default; COLUMN; has_failed
            3; event_default; COLUMN; error_code
            3; event_default; COLUMN; error_message
            3; event_default; COLUMN; error_trace
            3; event_default; COLUMN; source_channel
            3; event_default; COLUMN; source_owner
            3; event_default; REVERSE FOREIGN KEY; event_default_aggregate_id_fkey ON event_default (aggregate_id) REFERENCES event_index (aggregate_id)
            4; event_default; TABLE; event_index
            5; event_default; PRIMARY KEY; event_index_pkey ON event_index (aggregate_id)
            5; event_default; COLUMN; aggregate_id
            5; event_default; COLUMN; aggregate_type
            5; event_default; COLUMN; aggregate_root
            5; event_default; COLUMN; namespace
            5; event_default; COLUMN; created_at
            5; event_default; REVERSE FOREIGN KEY; event_index_aggregate_root_fkey ON event_index (aggregate_root) REFERENCES event_index (aggregate_id)
            5; event_default; FOREIGN KEY; event_default_aggregate_id_fkey ON event_default (aggregate_id) REFERENCES event_index (aggregate_id)
            2; event_index; TABLE; event_index
            3; event_index; PRIMARY KEY; event_index_pkey ON event_index (aggregate_id)
            3; event_index; COLUMN; aggregate_id
            3; event_index; COLUMN; aggregate_type
            3; event_index; COLUMN; aggregate_root
            3; event_index; COLUMN; namespace
            3; event_index; COLUMN; created_at
            3; event_index; REVERSE FOREIGN KEY; event_index_aggregate_root_fkey ON event_index (aggregate_root) REFERENCES event_index (aggregate_id)
            3; event_index; FOREIGN KEY; event_default_aggregate_id_fkey ON event_default (aggregate_id) REFERENCES event_index (aggregate_id)
            2; message_broker; TABLE; message_broker
            3; message_broker; PRIMARY KEY; message_broker_pkey ON message_broker (serial)
            3; message_broker; COLUMN; id
            3; message_broker; COLUMN; serial
            3; message_broker; COLUMN; queue
            3; message_broker; COLUMN; created_at
            3; message_broker; COLUMN; consumed_at
            3; message_broker; COLUMN; has_failed
            3; message_broker; COLUMN; headers
            3; message_broker; COLUMN; type
            3; message_broker; COLUMN; content_type
            3; message_broker; COLUMN; body
            3; message_broker; COLUMN; error_code
            3; message_broker; COLUMN; error_message
            3; message_broker; COLUMN; error_trace
            3; message_broker; COLUMN; retry_count
            3; message_broker; COLUMN; retry_at
            2; message_broker_dead_letters; TABLE; message_broker_dead_letters
            3; message_broker_dead_letters; PRIMARY KEY; message_broker_dead_letters_pkey ON message_broker_dead_letters (id)
            3; message_broker_dead_letters; COLUMN; id
            3; message_broker_dead_letters; COLUMN; serial
            3; message_broker_dead_letters; COLUMN; queue
            3; message_broker_dead_letters; COLUMN; created_at
            3; message_broker_dead_letters; COLUMN; consumed_at
            3; message_broker_dead_letters; COLUMN; headers
            3; message_broker_dead_letters; COLUMN; type
            3; message_broker_dead_letters; COLUMN; content_type
            3; message_broker_dead_letters; COLUMN; body
            3; message_broker_dead_letters; COLUMN; error_code
            3; message_broker_dead_letters; COLUMN; error_message
            3; message_broker_dead_letters; COLUMN; error_trace
            TXT,
            \trim($visitor->getOutput())
        );
    }
     */
}
