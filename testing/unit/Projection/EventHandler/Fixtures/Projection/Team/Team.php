<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team;

use Honeybee\Projection\Projection;

class Team extends Projection
{
    public function getName()
    {
        return $this->getValue('name');
    }
}
