<?php

namespace Honeybee\Tests\Fixture\BookSchema\Projection\Publisher;

use Honeybee\Projection\Resource\Resource;

class Publisher extends Resource
{
    public function getName()
    {
        return $this->get('name');
    }

    public function setName($name)
    {
        return $this->setValue('name', $name);
    }

    public function getDescription()
    {
        return $this->get('description');
    }

    public function setDescription($description)
    {
        return $this->setValue('description', $description);
    }
}
