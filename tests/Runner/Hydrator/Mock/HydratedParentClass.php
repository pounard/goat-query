<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Hydrator\Mock;

class HydratedParentClass
{
    private $miaw;

    public function getMiaw()
    {
        return $this->miaw;
    }
}
