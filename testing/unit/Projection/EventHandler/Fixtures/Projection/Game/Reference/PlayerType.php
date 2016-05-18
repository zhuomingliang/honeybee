<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\Reference;

use Honeybee\Projection\ReferencedEntityType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\ProjectionType;
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
                new Text('name', $this, [ 'mirrored' => true ], $parent_attribute),
                new EmbeddedEntityListAttribute(
                    'profiles',
                    $this,
                    [
                        'entity_types' => [
                            ProjectionType::NAMESPACE_PREFIX . 'Game\\Embed\\ProfileType',
                        ]
                    ],
                    $parent_attribute
                ),
            ],
            new Options(
                [
                    'referenced_type' => ProjectionType::NAMESPACE_PREFIX . 'Player\\PlayerType',
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
