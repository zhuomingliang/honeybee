<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team;

use Trellis\Common\Options;
use Trellis\Runtime\Attribute\Text\TextAttribute as Text;
use Workflux\StateMachine\StateMachineInterface;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\ProjectionType;

class TeamType extends ProjectionType
{
    public function __construct(StateMachineInterface $state_machine)
    {
        parent::__construct('Team', $state_machine);
    }

    public function getDefaultAttributes()
    {
        return array_merge(
            parent::getDefaultAttributes(),
            [
                new Text('name', $this, [ 'mandatory' => true ]),
            ]
        );
    }

    public static function getEntityImplementor()
    {
        return Team::CLASS;
    }
}
