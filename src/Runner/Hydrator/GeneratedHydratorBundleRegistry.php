<?php

declare(strict_types=1);

namespace Goat\Runner\Hydrator;

use GeneratedHydrator\Bridge\Symfony\DeepHydrator;
use GeneratedHydrator\Bridge\Symfony\Hydrator;

class GeneratedHydratorBundleRegistry implements HydratorRegistry
{
    private Hydrator $hydrator;

    public function __construct(DeepHydrator $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * {@inheritdoc}
     */
    public function getHydrator(string $className): callable
    {
        return function (array $values) use ($className) {
            return $this->hydrator->createAndHydrate($className, $values);
        };
    }
}
