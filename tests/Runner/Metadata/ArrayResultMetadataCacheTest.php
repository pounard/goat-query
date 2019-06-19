<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Metadata;

use Goat\Runner\Metadata\ArrayResultMetadataCache;
use Goat\Runner\Metadata\ResultMetadataCache;

final class ArrayResultMetadataCacheTest extends AbstractResultMetadataCacheTest
{
    protected function createResultMetadataCache(): ResultMetadataCache
    {
        return new ArrayResultMetadataCache();
    }
}
