<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Hydrator\Mock;

final class RecursivelyHydratedClass extends HydratedParentClass
{
    private $foo;
    private $bar;

    /**
     * @return mixed
     */
    public function getFoo()
    {
        return $this->foo;
    }

    /**
     * @return null|RecursivelyHydratedClass
     */
    public function getBar()
    {
        return $this->bar;
    }
}
