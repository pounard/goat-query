<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Metadata;

use Goat\Runner\Metadata\ApcuResultMetadataCache;
use Goat\Runner\Metadata\ResultMetadataCache;

final class ApcuResultMetadataCacheTest extends AbstractResultMetadataCacheTest
{
    protected function createResultMetadataCache(): ResultMetadataCache
    {
        $apcuEnabled = \function_exists('apcu_add');
        $useApcu = $apcuEnabled && \getenv('ENABLE_APCU');

        if (!$useApcu) {
            $this->markTestSkipped("APCU is not enabled");
        }

        return new ApcuResultMetadataCache('bouh');
    }
}
