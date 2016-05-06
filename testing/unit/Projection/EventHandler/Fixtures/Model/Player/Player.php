<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Player;

use Honeybee\Model\Aggregate\AggregateRoot;

class Player extends AggregateRoot
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
