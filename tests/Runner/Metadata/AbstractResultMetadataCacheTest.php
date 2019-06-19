<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Metadata;

use Goat\Runner\Metadata\ResultMetadata;
use Goat\Runner\Metadata\ResultMetadataCache;
use PHPUnit\Framework\TestCase;

abstract class AbstractResultMetadataCacheTest extends TestCase
{
    protected abstract function createResultMetadataCache(): ResultMetadataCache;

    /**
     * Test basics.
     */
    public function testBasics()
    {
        $cache = $this->createResultMetadataCache();

        $this->assertNull($cache->fetch('foo'));
        $this->assertNull($cache->fetch('bar'));

        $cache->store('foo', ['a', 'bar', 'baz'], ['int', 'varchar', null]);

        $metadata = $cache->fetch('foo');
        $this->assertInstanceOf(ResultMetadata::class, $metadata);
        $this->assertSame('int', $metadata->getColumnType('a'));
        $this->assertNull($metadata->getColumnType('baz'));
        $this->assertTrue($metadata->columnExists('bar'));
        $this->assertFalse($metadata->columnExists('cassoulet'));
        $this->assertSame('bar', $metadata->getColumnName(1));
        $this->assertSame(3, $metadata->countColumns());
        $this->assertSame(['a', 'bar', 'baz'], $metadata->getColumnNames());
        $this->assertSame(['int', 'varchar', null], $metadata->getColumnTypes());

        $this->assertNull($cache->fetch('bar'));
    }
}
