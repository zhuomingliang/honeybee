<?php

namespace Honeybee\Tests\Fixture\BookSchema\Projection\Author\Reference;

use Honeybee\Projection\ReferencedEntityType;
use Trellis\Common\Options;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\EntityTypeInterface;

class BookType extends ReferencedEntityType
{
    public function __construct(EntityTypeInterface $parent = null, AttributeInterface $parent_attribute = null)
    {
        parent::__construct(
            'Book',
            [],
            new Options(
                [
                    'referenced_type' => '\\Honeybee\\Tests\\Fixture\\BookSchema\\Projection\\Book\\BookType',
                    'identifying_attribute' => 'identifier',
                ]
            ),
            $parent,
            $parent_attribute
        );
    }

    public static function getEntityImplementor()
    {
        return Book::CLASS;
    }
}
