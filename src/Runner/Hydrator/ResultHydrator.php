<?php

declare(strict_types=1);

namespace Goat\Runner\Hydrator;

use Goat\Hydrator\HydratorInterface;
use Goat\Query\QueryError;

final class ResultHydrator
{
    /** @var bool */
    private $hydratorIsCallable = false;

    /** @var null|callable|HydratorInterface */
    private $hydrator;

    /** @var null|string */
    private $separator;

    /** @var string[][] */
    private $groupCache = [];

    /** @var bool */
    private $doDropNullArrays = true;

    /**
     * @param callable|HydratorInterface $hydrator
     * @param string $separator
     * @param bool $dropNullArrays
     *   If an array contains only null values during nested property hydration
     *   then replace it will null simply.
     */
    public function __construct($hydrator = null, ?string $separator = '.', bool $dropNullArrays = true)
    {
        $this->separator = $separator;
        $this->doDropNullArrays = $dropNullArrays;

        if (null === $hydrator) {
            $this->hydrator = null;
        } else if (\is_callable($hydrator)) {
            $this->hydrator = $hydrator;
            $this->hydratorIsCallable = true;
        } else if ($hydrator instanceof HydratorInterface) {
            $this->hydrator = $hydrator;
            $this->hydratorIsCallable = false;
        } else {
            throw new QueryError(\sprintf("Result hydrator must be a callable or an instance of %s", HydratorInterface::class));
        }
    }

    public function hydrate(array $row)
    {
        if ($this->separator) {
            $row = $this->aggregate($row);
        }

        if ($this->hydratorIsCallable) {
            return ($this->hydrator)($row);
        } else if ($this->hydrator) {
            return $this->hydrator->createAndHydrateInstance($row);
        }
        return $row;
    }

    private function buildGroupNode(string $key, string $path, string $separator, array &$parent): void
    {
        $index = \strpos($key, $separator);

        // Also ignore when index is the first value.
        if (!$index) {
            $parent[$key] = $path;
        } else {
            $prefix = \substr($key, 0, $index);
            $suffix = \substr($key, $index + \strlen($separator));
            if (!isset($parent[$prefix]) || !\is_array($parent[$prefix])) {
                $parent[$prefix] = [];
            }
            $this->buildGroupNode($suffix, $path, $separator, $parent[$prefix]);
        }
    }

    private function buildGroups(array $keys): array
    {
        $ret = [];
        foreach ($keys as $key) {
            $this->buildGroupNode($key, $key, $this->separator, $ret);
        }
        return $ret;
    }

    private function propertyContentCanBeIgnored($value): bool
    {
        return null === $value || '' === $value || [] === $value;
    }

    private function handleNullArray(array $value): ?array
    {
        foreach ($value as $item) {
            if (null !== $item) {
                return $value;
            }
        }
        return null;
    }

    private function aggregatePropertiesOf(array $groups, array $values, $prefix = null): array
    {
        $ret = [];

        foreach ($groups as $key => $group) {
            $path = $prefix ? ($prefix . $this->separator . $key) : $key;

            // Do not allow value overwrite.
            if (\array_key_exists($key, $ret) && !$this->propertyContentCanBeIgnored($ret[$key])) {
                throw new \InvalidArgumentException(\sprintf(
                    "Nested property '%s' already has a value of type '%s'",
                    $path,
                    \gettype($ret[$key])
                ));
            }

            if (\is_string($group)) {
                $ret[$key] = $values[$group] ?? null;
            } else {
                $value = $this->aggregatePropertiesOf($group, $values, $path);
                if ($this->doDropNullArrays) {
                    $ret[$key] = $this->handleNullArray($value);
                } else {
                    $ret[$key] = $value;
                }
            }
        }

        return $ret;
    }

    /**
     * Create nested objects and set them in the new returned datas
     */
    private function aggregate(array $values)
    {
        // Build groups only once, the same ResultHydrator instance will
        // hydrate many rows along the way.
        if (null !== $this->groupCache) {
            $this->groupCache = $this->buildGroups(\array_keys($values));
        }

        return $this->aggregatePropertiesOf($this->groupCache, $values);
    }
}
