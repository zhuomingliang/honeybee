<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player;

use Honeybee\Projection\Projection;

class Player extends Projection
{
    public function getName()
    {
        return $this->getValue('name');
    }

    public function setName($name)
    {
        return $this->setValue('name', $name);
    }
}
