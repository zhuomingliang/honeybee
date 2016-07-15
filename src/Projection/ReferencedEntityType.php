<?php

namespace Honeybee\Projection;

use Honeybee\Common\Error\RuntimeError;
use Trellis\EntityType\Attribute\AttributeInterface;
use Trellis\EntityType\Attribute\AttributeMap;
use Trellis\EntityType\Attribute\Text\TextAttribute;
use Trellis\EntityType\TypeReferenceInterface;

abstract class ReferencedEntityType extends EmbeddedEntityType implements TypeReferenceInterface
{
    const OPTION_IDENTIFYING_ATTRIBUTE_NAME = 'identifying_attribute';

    const OPTION_REFERENCED_TYPE_CLASS = 'referenced_type';

    public function __construct(
        $name,
        array $attributes = [],
        array $options = [],
        AttributeInterface $parent_attribute = null
    ) {
        parent::__construct($name, $attributes, $options, $parent, $parent_attribute);

        if (!$this->hasOption(self::OPTION_IDENTIFYING_ATTRIBUTE_NAME)) {
            throw new RuntimeError(
                sprintf('Missing expected option "%s"', self::OPTION_IDENTIFYING_ATTRIBUTE_NAME)
            );
        }

        if (!$this->hasOption(self::OPTION_REFERENCED_TYPE_CLASS)) {
            throw new RuntimeError(
                sprintf('Missing expected option "%s"', self::OPTION_REFERENCED_TYPE_CLASS)
            );
        }
    }

    public function getReferencedAttributeName()
    {
        return $this->getOption(self::OPTION_IDENTIFYING_ATTRIBUTE_NAME);
    }

    public function getReferencedTypeClass()
    {
        return $this->getOption(self::OPTION_REFERENCED_TYPE_CLASS);
    }

    public function getDefaultAttributes()
    {
        $default_attributes = [
            new TextAttribute('referenced_identifier', $this, [], $this->getParentAttribute())
        ];

        $default_attributes_map = new AttributeMap($default_attributes);
        return parent::getDefaultAttributes()->append($default_attributes_map);
    }
}
