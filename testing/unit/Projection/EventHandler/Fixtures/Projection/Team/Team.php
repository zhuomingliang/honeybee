<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team;

use Honeybee\Projection\Projection;

class Team extends Projection
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
