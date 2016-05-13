<?php

namespace Honeybee\Projection\EventHandler;

use Honeybee\EntityInterface;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\Infrastructure\DataAccess\Query\AttributeCriteria;
use Honeybee\Infrastructure\DataAccess\Query\CriteriaList;
use Honeybee\Infrastructure\DataAccess\Query\Query;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceMap;
use Honeybee\Infrastructure\DataAccess\Query\Comparison\Equals;
use Honeybee\Infrastructure\DataAccess\Storage\StorageWriterMap;
use Honeybee\Infrastructure\Event\EventHandler;
use Honeybee\Infrastructure\Event\EventInterface;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Honeybee\Model\Event\AggregateRootEventInterface;
use Honeybee\Projection\ProjectionTypeMap;
use Psr\Log\LoggerInterface;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\AttributeValuePath;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;
use Trellis\Runtime\Entity\EntityList;
use Trellis\Runtime\Entity\EntityReferenceInterface;

class RelationProjectionUpdater extends EventHandler
{
    protected $storage_writer_map;

    protected $query_service_map;

    protected $projection_type_map;

    protected $aggregate_root_type_map;

    public function __construct(
        ConfigInterface $config,
        LoggerInterface $logger,
        StorageWriterMap $storage_writer_map,
        QueryServiceMap $query_service_map,
        ProjectionTypeMap $projection_type_map,
        AggregateRootTypeMap $aggregate_root_type_map
    ) {
        parent::__construct($config, $logger);

        $this->storage_writer_map = $storage_writer_map;
        $this->query_service_map = $query_service_map;
        $this->projection_type_map = $projection_type_map;
        $this->aggregate_root_type_map = $aggregate_root_type_map;
    }

    public function handleEvent(EventInterface $event)
    {
        $updated_projections = $this->updateAffectedRelatives($event);
        // @todo post ProjectionUpdatedEvent to the event-bus ('honeybee.projection_events' channel)
        return $updated_projections;
    }

    protected function updateAffectedRelatives(AggregateRootEventInterface $event)
    {
        $foreign_projection_type = $this->getProjectionType($event);
        $foreign_projection_type_impl = get_class($foreign_projection_type);

        $affected_attributes = array_keys($event->getData());
        foreach ($event->getEmbeddedEntityEvents() as $embedded_event) {
            $affected_attributes[] = $embedded_event->getParentAttributeName();
        }

        // build a list of referenced entity list attributes which are affected by this event
        $referenced_attributes = $this->getRelationProjectionType()->getReferenceAttributes()->filter(
            function (AttributeInterface $attribute) {
                $yield = true;
                while ($attribute->getParent() || !$attribute->getOption('mirrored', false)) {
                    if (!$attribute->getOption('mirrored', false)) {
                        $yield = false;
                        break;
                    }
                    $attribute = $attribute->getParent();
                }
                return $yield;
            }
        );

        // Determine whether any mirrored attributes exist on these referenced entities and if
        // so prepare a query to load any projections with matching relations for update
        $attributes_to_update = [];
        $reference_filter_list = new CriteriaList([], CriteriaList::OP_OR);
        foreach ($referenced_attributes as $attribute_path => $ref_attribute) {
            $mirror_attributes = [];
            foreach ($ref_attribute->getEmbeddedEntityTypeMap() as $reference_embed) {
                $referenced_type_impl = ltrim($reference_embed->getReferencedTypeClass(), '\\');
                if ($referenced_type_impl === $foreign_projection_type_impl) {
                    $attributes_to_mirror = $reference_embed->getAttributes()->filter(
                        function ($attribute) use ($affected_attributes) {
                            return in_array($attribute->getName(), $affected_attributes)
                                && (bool)$attribute->getOption('mirrored', false);
                        }
                    );
                    if (!$attributes_to_mirror->isEmpty()) {
                        $mirror_attributes[$reference_embed->getPrefix()] = $attributes_to_mirror->getKeys();
                    }
                }
            }

            // Add to the filter to load projections where mirrored attributes need to be updated
            if (!empty($mirror_attributes)) {
                $attributes_to_update[$attribute_path] = $mirror_attributes;
                $reference_filter_list->push(
                    new AttributeCriteria(
                        $this->buildFieldFilterSpec($ref_attribute),
                        new Equals($event->getAggregateRootIdentifier())
                    )
                );
            }
        }

        // finalize query and get results from the query service
        $updated_projections = new EntityList;
        if (!empty($reference_filter_list)) {
            $filter_criteria_list = new CriteriaList;
            $filter_criteria_list->push(
                new AttributeCriteria('identifier', new Equals('!' . $event->getAggregateRootIdentifier()))
            );
            if ($reference_filter_list->getSize() === 1) {
                $filter_criteria_list->push($reference_filter_list->getFirst());
            } else {
                $filter_criteria_list->push($reference_filter_list);
            }
            // @todo scan and scroll support
            $query_result = $this->getQueryService()->find(
                new Query(
                    new CriteriaList,
                    $filter_criteria_list,
                    new CriteriaList,
                    0,
                    10000
                )
            );

            // iterate the related projections from results and merge changes from event data
            $related_projections = new EntityList($query_result->getResults());
            $updated_projections->append($related_projections->withUpdatedEntities(
                $event->getData(),
                function (EntityInterface $entity) use ($event) {
                    return $entity instanceof EntityReferenceInterface
                    && $entity->getReferencedIdentifier() === $event->getAggregateRootIdentifier();
                }
            ));
        }

        // write updated projections to storage
        // @todo introduce a writeMany method to the storageWriter to save requests
        foreach ($updated_projections as $projection) {
            $this->getStorageWriter()->write($projection);
        }

        // drop the mic
        return $updated_projections;
    }

    protected function buildFieldFilterSpec(EmbeddedEntityListAttribute $embed_attribute)
    {
        $filter_parts = [];
        $parent_attribute = $embed_attribute->getParent();
        while ($parent_attribute) {
            $filter_parts[] = $parent_attribute->getName();
            $parent_attribute = $parent_attribute->getParent();
        }
        $filter_parts[] = $embed_attribute->getName();
        $filter_parts[] = 'referenced_identifier';

        return implode('.', $filter_parts);
    }

    protected function getProjectionType(AggregateRootEventInterface $event)
    {
        $ar_type = $this->aggregate_root_type_map->getByClassName($event->getAggregateRootType());

        if (!$this->projection_type_map->hasKey($ar_type->getPrefix())) {
            throw new RuntimeError('Unable to resolve projection type for prefix: ' . $ar_type->getPrefix());
        }

        return $this->projection_type_map->getItem($ar_type->getPrefix());
    }

    protected function getRelationProjectionType()
    {
        $projection_type_prefix = $this->config->get('projection_type');
        // @todo should the projection type map throw a runtime error internally on getItem?
        if (!$this->projection_type_map->hasKey($projection_type_prefix)) {
            throw new RuntimeError('Unable to resolve projection-type for prefix: ' . $projection_type_prefix);
        }

        return $this->projection_type_map->getItem($projection_type_prefix);
    }

    protected function getQueryService()
    {
        $query_service_default = sprintf(
            '%s::query_service',
            $this->getRelationProjectionType()->getPrefix()
        );

        $query_service_key = $this->config->get('query_service', $query_service_default);
        if (!$query_service_key || !$this->query_service_map->hasKey($query_service_key)) {
            throw new RuntimeError('Unable to resolve query_service for key: ' . $query_service_key);
        }

        return $this->query_service_map->getItem($query_service_key);
    }

    protected function getStorageWriter()
    {
        $storage_writer_default = sprintf(
            '%s::projection.standard::view_store::writer',
            $this->getRelationProjectionType()->getPrefix()
        );

        $storage_writer_key = $this->config->get('storage_writer', $storage_writer_default);
        if (!$this->storage_writer_map->hasKey($storage_writer_key)) {
            throw new RuntimeError('Unable to resolve storage_writer for key: ' . $storage_writer_key);
        }

        return $this->storage_writer_map->getItem($storage_writer_key);
    }
}
