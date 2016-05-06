<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\Reference;

use Honeybee\Projection\ReferencedEntityType;
use Trellis\Common\Options;
use Trellis\Runtime\EntityTypeInterface;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\Text\TextAttribute as Text;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;

class PlayerType extends ReferencedEntityType
{
    public function __construct(EntityTypeInterface $parent = null, AttributeInterface $parent_attribute = null)
    {
        parent::__construct(
            'Player',
            [
                new Text('name', $this, [ 'mirrored' => true ]),
                new EmbeddedEntityListAttribute(
                    'profiles',
                    $this,
                    [
                        'mirrored' => true,
                        'entity_types' => [
                            '\\Honeybee\\Tests\\Projection\\EventHandler\\Fixtures\\Projection\\Game\\Embed\\ProfileType',
                        ]
                    ]
                ),
                new EmbeddedEntityListAttribute(
                    'unmirrored_profiles',
                    $this,
                    [
                        'mirrored' => false,
                        'entity_types' => [
                            '\\Honeybee\\Tests\\Projection\\EventHandler\\Fixtures\\Projection\\Game\\Embed\\ProfileType',
                        ]
                    ]
                ),
            ],
            new Options(
                [
                    'referenced_type' => '\\Honeybee\\Tests\\Projection\\EventHandler\\Fixtures\\Projection\\Player\\PlayerType',
                    'identifying_attribute' => 'identifier',
                ]
            ),
            $parent,
            $parent_attribute
        );
    }

    public static function getEntityImplementor()
    {
        return Player::CLASS;
    }
}
