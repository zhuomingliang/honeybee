<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\Embed;

use Honeybee\EntityType;
use Trellis\Common\Options;
use Trellis\Runtime\EntityTypeInterface;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\Integer\IntegerAttribute;

class ChallengeType extends EntityType
{
    public function __construct(EntityTypeInterface $parent = null, AttributeInterface $parent_attribute = null)
    {
        parent::__construct(
            'Challenge',
            [
                new IntegerAttribute('attempts', $this, [ 'mirrored' => true ], $parent_attribute),
            ],
            new Options([]),
            $parent,
            $parent_attribute
        );
    }

    public static function getEntityImplementor()
    {
        return Challenge::CLASS;
    }
}
