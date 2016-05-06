<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Game;

use Honeybee\Model\Aggregate\AggregateRoot;

class Game extends AggregateRoot
{
    public function getTitle()
    {
        return $this->getValue('title');
    }

    public function setTitle($title)
    {
        return $this->setValue('title', $title);
    }
}
