<?php

declare(strict_types=1);

namespace Goat\Runner\Hydrator;

use Goat\Query\QueryError;
use Goat\Runner\Row;

final class ResultHydrator
{
    /** For group extension (disabled per default). */
    private ?string $separator = null;
    private bool $doExpandGroups = false;
    private bool $doDropNullArrays = true;

    /** @var null|callable */
    private $hydrator = null;
    /** @var string[][] */
    private array $groupCache = [];

    /** For backward compatibility */
    private bool $needsColumnHydration = false;

    public function __construct(
        ?callable $hydrator = null,
        bool $expandGroups = false,
        ?string $separator = '.',
        bool $dropNullArrays = true
    ) {
        if ($this->doExpandGroups = $expandGroups) {
            @\trigger_error("Automatic group expansion is not the default anymore, and is deprecated and will be removed in the next major, please consider writing custom hydrators instead.", E_USER_DEPRECATED);
            $this->separator = $separator;
            $this->doDropNullArrays = $dropNullArrays;
        }

        if (null === $hydrator) {
            $this->hydrator = null;
        } else if (\is_callable($hydrator)) {
            $this->hydrator = $this->handleBackwardCompatibility($hydrator);
        } else {
            throw new QueryError("Result hydrator must be a callable.");
        }
    }

    /**
     * @deprecated
     *   Exists for backward compatibility.
     * @internal
     *   Do not use this methods as it will be removed in next major.
     */
    public function needsColumnHydration(): bool
    {
        return $this->needsColumnHydration;
    }

    /**
     * @deprecated
     *   Exists for backward compatibility.
     */
    private function handleBackwardCompatibility(callable $hydrator): callable
    {
        $hydrator = \Closure::fromCallable($hydrator);

        // This is backward compatibility code check only, and will be
        // removed in a next major.
        $reflection = new \ReflectionFunction($hydrator);
        if ($parameters = $reflection->getParameters()) {
            \assert($parameters[0] instanceof \ReflectionParameter);
            if ($parameters[0]->hasType() && ($type = $parameters[0]->getType())) {
                \assert($type instanceof \ReflectionType);
                if ($type instanceof \ReflectionNamedType) {
                    if ($type->getName() === Row::class) {
                        // OK.
                    } else {
                        @\trigger_error(\sprintf("Given hydrator provides %s as first parameter, assume 'array', this warning will be removed in next major, and first parameter will always be a %s instance.", $type->getName(), Row::class), E_USER_DEPRECATED);
                        $this->needsColumnHydration = true;
                    }
                } else {
                    @\trigger_error(\sprintf("Given hydrator provides a union type for its first parameter, assume 'array', this warning will be removed in next major, and first parameter will always be a %s instance.", Row::class), E_USER_DEPRECATED);
                    $this->needsColumnHydration = true;
                }
            } else {
                // Hydrator exposes no type, consider it's new API. It may break
                // some code, but with a warning users at least will know why.
                @\trigger_error(\sprintf("Given hydrator does not provide a type for its first parameter, assume 'array', this warning will be removed in next major, and first parameter will always be a %s instance.", Row::class), E_USER_DEPRECATED);
                $this->needsColumnHydration = true;
            }
        }

        return $hydrator;
    }

    public function hydrate(Row $row)
    {
        if ($this->doExpandGroups && $this->separator) {
            $row = $this->aggregate($row->toHydratedArray());
        }

        if ($this->hydrator) {
            if ($this->needsColumnHydration) {
                return ($this->hydrator)($row->toHydratedArray());
            }
            return ($this->hydrator)($row);
        }

        return $row;
    }

    /**
     * @deprecated
     *   Exists for backward compatibility.
     */
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

    /**
     * @deprecated
     *   Exists for backward compatibility.
     */
    private function buildGroups(array $keys): array
    {
        $ret = [];
        foreach ($keys as $key) {
            $this->buildGroupNode($key, $key, $this->separator, $ret);
        }
        return $ret;
    }

    /**
     * @deprecated
     *   Exists for backward compatibility.
     */
    private function propertyContentCanBeIgnored($value): bool
    {
        return null === $value || '' === $value;
    }

    /**
     * @deprecated
     *   Exists for backward compatibility.
     */
    private function handleNullArray(array $value): ?array
    {
        foreach ($value as $item) {
            if (null !== $item) {
                return $value;
            }
        }
        return null;
    }

    /**
     * @deprecated
     *   Exists for backward compatibility.
     */
    private function aggregatePropertiesOf(array $groups, array $values, $prefix = null): array
    {
        $ret = [];

        foreach ($groups as $key => $group) {
            $path = $prefix ? ($prefix . $this->separator . $key) : $key;

            // Do not allow value overwrite.
            if (\array_key_exists($key, $ret) && !$this->propertyContentCanBeIgnored($ret[$key])) {
                throw new QueryError(\sprintf(
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
     * @deprecated
     *   Exists for backward compatibility.
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
