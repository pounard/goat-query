<?php

declare(strict_types=1);

namespace Goat\Runner\Metadata;

/**
 * Array based implementation for testing, mostly.
 */
class ArrayResultMetadataCache implements ResultMetadataCache
{
    private $data = [];

    /**
     * {@inheritdoc}
     */
    public function store(string $identifier, array $names, array $types): void
    {
        $this->data[$identifier] = [$names, $types];
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $identifier): ?ResultMetadata
    {
        if (isset($this->data[$identifier])) {
            return new DefaultResultMetadata(...$this->data[$identifier]);
        }
        return null;
    }
}
