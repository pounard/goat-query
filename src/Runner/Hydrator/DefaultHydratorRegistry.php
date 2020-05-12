<?php

declare(strict_types=1);

namespace Goat\Runner\Hydrator;

use GeneratedHydrator\Bridge\Symfony\DeepHydrator;
use GeneratedHydrator\Bridge\Symfony\DefaultHydrator;

/**
 * Very slow yet working implementation.
 */
class DefaultHydratorRegistry implements HydratorRegistry
{
    /**
     * Create default instance by introspecting runtime capabilities.
     */
    public static function createDefaultInstance(): HydratorRegistry
    {
        if (\class_exists(DeepHydrator::class)) {
            return new GeneratedHydratorBundleRegistry(
                new DeepHydrator(
                    new DefaultHydrator(
                        \sys_get_temp_dir()
                    )
                )
            );
        }

        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function getHydrator(string $className): callable
    {
        return static function (array $values) use ($className) {
            $ref = new \ReflectionClass($className);
            $object = $ref->newInstanceWithoutConstructor();

            do {
                $ref = new \ReflectionClass($className);
                $fun = static function ($values) use ($ref, $object) {
                    foreach ($values as $key => $value) {
                        // This will crash with PHP 7.4 unitialized properties.
                        // You've been warned.
                        if ($ref->hasProperty($key)) {
                            $property = $ref->getProperty($key);
                            if (!$property->isStatic()) {
                                $object->{$key} = $value;
                            }
                        }
                    }
                };
                (\Closure::bind($fun, null, $className))($values);
            } while ($className = \get_parent_class($className));

            return $object;
        };
    }
}
