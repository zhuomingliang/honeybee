<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game;

use Trellis\Common\Options;
use Trellis\Runtime\Attribute\Text\TextAttribute as Text;
use Trellis\Runtime\Attribute\EntityReferenceList\EntityReferenceListAttribute;
use Workflux\StateMachine\StateMachineInterface;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\ProjectionType;

class GameType extends ProjectionType
{
    public function __construct(StateMachineInterface $state_machine)
    {
        parent::__construct('Game', $state_machine);
    }

    public function getDefaultAttributes()
    {
        return array_merge(
            parent::getDefaultAttributes(),
            [
                new Text('title', $this, [ 'mandatory' => true ]),
                new EntityReferenceListAttribute(
                    'players',
                    $this,
                    [
                        'entity_types' => [
                            '\\Honeybee\\Tests\\Projection\\EventHandler\\Fixtures\\Projection\\Game\\Reference\\PlayerType',
                        ]
                    ]
                )
            ]
        );
    }

    public static function getEntityImplementor()
    {
        return Game::CLASS;
    }
}
