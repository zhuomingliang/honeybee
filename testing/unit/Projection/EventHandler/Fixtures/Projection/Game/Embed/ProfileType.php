<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\Embed;

use Honeybee\EntityType;
use Trellis\Common\Options;
use Trellis\Runtime\EntityTypeInterface;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\Text\TextAttribute as Text;
use Trellis\Runtime\Attribute\TextList\TextListAttribute;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;
use Trellis\Runtime\Attribute\EntityReferenceList\EntityReferenceListAttribute;

class ProfileType extends EntityType
{
    public function __construct(EntityTypeInterface $parent = null, AttributeInterface $parent_attribute = null)
    {
        parent::__construct(
            'Profile',
            [
                new Text('alias', $this, [], $parent_attribute),
                new TextListAttribute('tags', $this, [], $parent_attribute),
                new EntityReferenceListAttribute(
                    'teams',
                    $this,
                    [
                        'entity_types' => [
                            '\\Honeybee\\Tests\\Projection\\EventHandler\\Fixtures\\Projection\\Game\\Reference\\TeamType',
                        ]
                    ],
                    $parent_attribute
                ),
                new EmbeddedEntityListAttribute(
                    'badges',
                    $this,
                    [
                        'entity_types' => [
                            '\\Honeybee\\Tests\\Projection\\EventHandler\\Fixtures\\Projection\\Game\\Embed\\BadgeType',
                        ]
                    ],
                    $parent_attribute
                ),
                new EmbeddedEntityListAttribute(
                    'unmirrored_badges',
                    $this,
                    [
                        'entity_types' => [
                            '\\Honeybee\\Tests\\Projection\\EventHandler\\Fixtures\\Projection\\Game\\Embed\\BadgeType',
                        ]
                    ],
                    $parent_attribute
                ),
            ],
            new Options([]),
            $parent,
            $parent_attribute
        );
    }

    public static function getEntityImplementor()
    {
        return Profile::CLASS;
    }
}
