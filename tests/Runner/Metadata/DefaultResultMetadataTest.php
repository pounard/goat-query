<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Metadata;

use Goat\Runner\Metadata\DefaultResultMetadata;
use Goat\Runner\Metadata\ResultMetadata;
use PHPUnit\Framework\TestCase;
use Goat\Runner\InvalidDataAccessError;

class DefaultResultMetadataTest extends TestCase
{
    /**
     * Test basics.
     */
    public function testBasics()
    {
        $metadata = new DefaultResultMetadata(['a', 'bar', 'baz'], ['int', 'varchar', null]);

        $this->assertInstanceOf(ResultMetadata::class, $metadata);
        $this->assertSame('int', $metadata->getColumnType('a'));
        $this->assertNull($metadata->getColumnType('baz'));
        $this->assertTrue($metadata->columnExists('bar'));
        $this->assertFalse($metadata->columnExists('cassoulet'));
        $this->assertSame('bar', $metadata->getColumnName(1));
        $this->assertSame(3, $metadata->countColumns());
        $this->assertSame(['a', 'bar', 'baz'], $metadata->getColumnNames());
        $this->assertSame(['int', 'varchar', null], $metadata->getColumnTypes());
    }

    public function testConstructRaiseErrorOnCountMismatch()
    {
        $this->expectException(InvalidDataAccessError::class);
        $this->expectExceptionMessageRegExp('/column.*count.*mismatch/');

        new DefaultResultMetadata(['a', 'bar', 'baz'], [], 4);
    }

    public function testNegativeIndexRaiseErrorOnCountMismatch()
    {
        $metadata = new DefaultResultMetadata(['a', 'bar', 'baz'], ['int', 'varchar', null]);

        $this->expectException(InvalidDataAccessError::class);
        $this->expectExceptionMessageRegExp('/column count start with/');

        $metadata->getColumnName(-1);
    }

    public function testOutOfBoundIndexRaiseErrorOnCountMismatch()
    {
        $metadata = new DefaultResultMetadata(['a', 'bar', 'baz'], ['int', 'varchar', null]);

        $this->expectException(InvalidDataAccessError::class);
        $this->expectExceptionMessageRegExp('/column count is/');

        $metadata->getColumnName(10);
    }
}
