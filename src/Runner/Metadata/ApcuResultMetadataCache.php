<?php

declare(strict_types=1);

namespace Goat\Runner\Metadata;

/**
 * APCu based result metadata cache. Simple, fast, good for most usages.
 */
class ApcuResultMetadataCache implements ResultMetadataCache
{
    private $prefix;

    /**
     * Default constructor
     */
    public function __construct(?string $prefix = null)
    {
        $this->prefix = $prefix ? $prefix.'_goat_query_' : 'goat_query_';
    }

    /**
     * {@inheritdoc}
     */
    public function store(string $identifier, array $names, array $types): void
    {
        \apcu_add($this->prefix.$identifier, [$names, $types]);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $identifier): ?ResultMetadata
    {
        if ($values = \apcu_fetch($this->prefix.$identifier)) {

            // OK, we need to make sure everything's right here and we don't
            // fetch invalid values, no matter the reason why there could be
            // invalid values there.
            if (!\count($values) === 2 || !\is_array($values[0]) || !\is_array($values[1])) {
                \apcu_delete($this->prefix.$identifier);

                return null;
            }

            return new DefaultResultMetadata(...$values);
        }

        return null;
    }
}
