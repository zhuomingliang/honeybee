<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Player\Embed;

use Honeybee\Model\Aggregate\EmbeddedEntityType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Model\EntityType;
use Trellis\Common\Options;
use Trellis\Runtime\EntityTypeInterface;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\Text\TextAttribute as Text;
use Trellis\Runtime\Attribute\TextList\TextListAttribute;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;

class ProfileType extends EmbeddedEntityType
{
    public function __construct(EntityTypeInterface $parent = null, AttributeInterface $parent_attribute = null)
    {
        parent::__construct(
            'Profile',
            [
                new Text('alias', $this, [], $parent_attribute),
                new TextListAttribute('tags', $this, [], $parent_attribute),
                new EmbeddedEntityListAttribute(
                    'badges',
                    $this,
                    [
                        'entity_types' => [
                            EntityType::NAMESPACE_PREFIX . 'Player\\Embed\\BadgeType',
                        ]
                    ],
                    $parent_attribute
                ),
                new EmbeddedEntityListAttribute(
                    'unmirrored_badges',
                    $this,
                    [
                        'entity_types' => [
                            EntityType::NAMESPACE_PREFIX . 'Player\\Embed\\BadgeType',
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
