<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Team;

use Honeybee\Model\Aggregate\AggregateRoot;

class Team extends AggregateRoot
{
    public function getName()
    {
        return $this->getValue('name');
    }
}
