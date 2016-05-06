<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player;

use Trellis\Common\Options;
use Trellis\Runtime\Attribute\Text\TextAttribute as Text;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;
use Workflux\StateMachine\StateMachineInterface;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\ProjectionType;

class PlayerType extends ProjectionType
{
    public function __construct(StateMachineInterface $state_machine)
    {
        parent::__construct('Player', $state_machine);
    }

    public function getDefaultAttributes()
    {
        return array_merge(
            parent::getDefaultAttributes(),
            [
                new Text('name', $this, [ 'mandatory' => true ]),
                new EmbeddedEntityListAttribute(
                    'profiles',
                    $this,
                    [
                        'entity_types' => [
                            '\\Honeybee\\Tests\\Projection\\EventHandler\\Fixtures\\Projection\\Player\\Embed\\ProfileType',
                        ]
                    ]
                ),
                new EmbeddedEntityListAttribute(
                    'unmirrored_profiles',
                    $this,
                    [
                        'entity_types' => [
                            '\\Honeybee\\Tests\\Projection\\EventHandler\\Fixtures\\Projection\\Player\\Embed\\ProfileType',
                        ]
                    ]
                ),
            ]
        );
    }

    public static function getEntityImplementor()
    {
        return Player::CLASS;
    }
}
