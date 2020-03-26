<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Hydrator\Mock;

final class HydratedClass extends HydratedParentClass
{
    private static $miaw;
    protected static $waf;
    public static $moo;

    public $constructorHasRun = false;
    public $constructorHasRunWithData = false;

    private $foo;
    protected $bar;
    public $baz;

    public function __construct()
    {
        if ($this->foo) {
            $this->constructorHasRunWithData = true;
        }
        $this->constructorHasRun = true;
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function getBar()
    {
        return $this->bar;
    }

    public function getBaz()
    {
        return $this->baz;
    }

    private $someNestedInstance;

    /**
     * Only in use in HydratorTest::testNesting()
     *
     * @return HydratedParentClass
     */
    public function getSomeNestedInstance()
    {
        return $this->someNestedInstance;
    }

    /**
     * @Goat\Hydrator\Annotation\Property(class=Goat\Tests\Hydrator\HydratedParentClass)
     */
    private $annotedNestedInstance;

    public function getAnnotedNestedInstance()
    {
        return $this->annotedNestedInstance;
    }
}
