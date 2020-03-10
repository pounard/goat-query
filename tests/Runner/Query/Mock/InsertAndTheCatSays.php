<?php

declare(strict_types=1);

namespace Goat\Runner\Tests\Query\Mock;

class InsertAndTheCatSays
{
    private $id;
    private $miaw;

    public function getId() : int
    {
        return $this->id;
    }

    public function miaw() : string
    {
        return $this->miaw;
    }
}
