<?php

namespace Honeybee\Projection\EventHandler;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\EntityTypeInterface;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\Infrastructure\DataAccess\DataAccessServiceInterface;
use Honeybee\Infrastructure\DataAccess\Query\AttributeCriteria;
use Honeybee\Infrastructure\DataAccess\Query\CriteriaList;
use Honeybee\Infrastructure\DataAccess\Query\Query;
use Honeybee\Infrastructure\DataAccess\Query\Comparison\Equals;
use Honeybee\Infrastructure\Event\EventHandler;
use Honeybee\Infrastructure\Event\EventInterface;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Honeybee\Model\Event\AggregateRootEventInterface;
use Honeybee\Model\Event\EmbeddedEntityEventInterface;
use Honeybee\Model\Event\EmbeddedEntityEventList;
use Honeybee\Model\Task\CreateAggregateRoot\AggregateRootCreatedEvent;
use Honeybee\Model\Task\ModifyAggregateRoot\AggregateRootModifiedEvent;
use Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\EmbeddedEntityAddedEvent;
use Honeybee\Model\Task\ModifyAggregateRoot\ModifyEmbeddedEntity\EmbeddedEntityModifiedEvent;
use Honeybee\Model\Task\ModifyAggregateRoot\RemoveEmbeddedEntity\EmbeddedEntityRemovedEvent;
use Honeybee\Model\Task\MoveAggregateRootNode\AggregateRootNodeMovedEvent;
use Honeybee\Model\Task\ProceedWorkflow\WorkflowProceededEvent;
use Honeybee\Projection\ProjectionInterface;
use Honeybee\Projection\ProjectionTypeInterface;
use Honeybee\Projection\ProjectionTypeMap;
use Honeybee\Projection\ProjectionUpdatedEvent;
use Trellis\Runtime\Attribute\AttributeMap;
use Trellis\Runtime\Attribute\AttributeValuePath;
use Trellis\Runtime\Entity\EntityInterface;
use Trellis\Runtime\Entity\EntityList;
use Trellis\Runtime\Entity\EntityReferenceInterface;
use Trellis\Runtime\ReferencedEntityTypeInterface;
use Psr\Log\LoggerInterface;

class ProjectionUpdater extends EventHandler
{
    protected $data_access_service;

    protected $projection_type_map;

    protected $aggregate_root_type_map;

    public function __construct(
        ConfigInterface $config,
        LoggerInterface $logger,
        DataAccessServiceInterface $data_access_service,
        ProjectionTypeMap $projection_type_map,
        AggregateRootTypeMap $aggregate_root_type_map
    ) {
        parent::__construct($config, $logger);

        $this->data_access_service = $data_access_service;
        $this->projection_type_map = $projection_type_map;
        $this->aggregate_root_type_map = $aggregate_root_type_map;
    }

    public function handleEvent(EventInterface $event)
    {
        $affected_entities = new EntityList;

        $projection = $this->invokeEventHandler($event, 'on');

        if ($projection) {
            $affected_entities->push($projection);
            $this->invokeEventHandler($event, 'after', [ $projection ]);
            $updated_event = new ProjectionUpdatedEvent([
                'uuid' => $projection->getUuid(),
                'source_event_data' => $event->toArray(),
                'projection_type' => $projection->getType()->getPrefix(),
                'projection_data' => $projection->toArray()
            ]);
            // @todo post ProjectionUpdatedEvent to the event-bus ('honeybee.projection_events' channel)
        }

        return $affected_entities;
    }

    protected function onAggregateRootCreated(AggregateRootCreatedEvent $event)
    {
        $projection_data = $event->getData();
        $projection_data['identifier'] = $event->getAggregateRootIdentifier();
        $projection_data['revision'] = $event->getSeqNumber();
        $projection_data['created_at'] = $event->getDateTime();
        $projection_data['modified_at'] = $event->getDateTime();
        $projection_data['metadata'] = $event->getMetaData();

        $new_projection = $this->getProjectionType($event)->createEntity($projection_data);
        $this->handleEmbeddedEntityEvents($new_projection, $event->getEmbeddedEntityEvents());
        $this->getStorageWriter($event)->write($new_projection);

        return $new_projection;
    }

    protected function onAggregateRootModified(AggregateRootModifiedEvent $event)
    {
        $updated_data = $this->loadProjection($event)->toArray();

        foreach ($event->getData() as $attribute_name => $new_value) {
            $updated_data[$attribute_name] = $new_value;
        }
        $updated_data['revision'] = $event->getSeqNumber();
        $updated_data['modified_at'] = $event->getDateTime();
        $updated_data['metadata'] = array_merge($updated_data['metadata'], $event->getMetaData());

        $projection = $this->getProjectionType($event)->createEntity($updated_data);

        $this->handleEmbeddedEntityEvents($projection, $event->getEmbeddedEntityEvents());
        $this->getStorageWriter($event)->write($projection);

        return $projection;
    }

    protected function onWorkflowProceeded(WorkflowProceededEvent $event)
    {
        $updated_data = $this->loadProjection($event)->toArray();
        $updated_data['revision'] = $event->getSeqNumber();
        $updated_data['modified_at'] = $event->getDateTime();
        $updated_data['metadata'] = array_merge($updated_data['metadata'], $event->getMetaData());
        $updated_data['workflow_state'] = $event->getWorkflowState();
        $workflow_parameters = $event->getWorkflowParameters();
        if ($workflow_parameters !== null) {
            $updated_data['workflow_parameters'] = $workflow_parameters;
        }

        $projection = $this->getProjectionType($event)->createEntity($updated_data);
        $this->getStorageWriter($event)->write($projection);

        return $projection;
    }

    protected function onAggregateRootNodeMoved(AggregateRootNodeMovedEvent $event)
    {
        $parent_projection = $this->loadProjection($event, $event->getParentNodeId());
        $child_projection = $this->loadProjection($event);

        $new_child_path = $parent_projection->getMaterializedPath() . '/' . $event->getParentNodeId();
        $child_data = $child_projection->toArray();
        $child_data['revision'] = $event->getSeqNumber();
        $child_data['modified_at'] = $event->getDateTime();
        $child_data['metadata'] = array_merge($child_data['metadata'], $event->getMetaData());
        $child_data['parent_node_id'] = $event->getParentNodeId();
        $child_data['materialized_path'] = $new_child_path;
        $this->getStorageWriter($event)->write(
            $this->getProjectionType($event)->createEntity($child_data)
        );

        $child_path_parts = [ $child_projection->getMaterializedPath(), $child_projection->getIdentifier() ];
        $recursive_children_result = $this->getQueryService()->find(
            // @todo scan and scroll support
            new Query(
                new CriteriaList,
                new CriteriaList(
                    [ new AttributeCriteria('materialized_path', new Equals(implode('/', $child_path_parts))) ]
                ),
                new CriteriaList,
                0,
                10000
            )
        );
        foreach ($recursive_children_result->getResults() as $affected_ancestor) {
            $ancestor_data = $affected_ancestor->toArray();
            $ancestor_data['materialized_path'] = str_replace(
                $child_projection->getMaterializedPath(),
                $new_child_path,
                $affected_ancestor->getMaterializedPath()
            );
            // @todo introduce a writeMany method to the storageWriter to save requests
            $this->getStorageWriter($event)->write(
                $this->getProjectionType($event)->createEntity($ancestor_data)
            );
        }

        return $child_projection;
    }

    protected function handleEmbeddedEntityEvents(EntityInterface $projection, EmbeddedEntityEventList $events)
    {
        $aggregate_data = [];

        foreach ($events as $event) {
            if ($event instanceof EmbeddedEntityAddedEvent) {
                $this->onEmbeddedEntityAdded($projection, $event);
            } elseif ($event instanceof EmbeddedEntityModifiedEvent) {
                $this->onEmbeddedEntityModified($projection, $event);
            } elseif ($event instanceof EmbeddedEntityRemovedEvent) {
                $this->onEmbeddedEntityRemoved($projection, $event);
            } else {
                throw new RuntimeError(
                    sprintf(
                        'Unsupported domain event-type given. Supported default event-types are: %s.',
                        implode(
                            ', ',
                            [
                                EmbeddedEntityAddedEvent::CLASS,
                                EmbeddedEntityModifiedEvent::CLASS,
                                EmbeddedEntityRemovedEvent::CLASS
                            ]
                        )
                    )
                );
            }
        }

        return $aggregate_data;
    }

    protected function onEmbeddedEntityAdded(EntityInterface $projection, EmbeddedEntityAddedEvent $event)
    {
        $embedded_projection_attr = $projection->getType()->getAttribute($event->getParentAttributeName());
        $embedded_projection_type = $this->getEmbeddedEntityTypeFor($projection, $event);
        $embedded_projection = $embedded_projection_type->createEntity($event->getData(), $projection);
        $projection_list = $projection->getValue($embedded_projection_attr->getName());
        if ($embedded_projection_type instanceof ReferencedEntityTypeInterface) {
            $embedded_projection = $this->mirrorReferencedValues($embedded_projection);
        }

        $projection_list->insertAt($event->getPosition(), $embedded_projection);

        $this->handleEmbeddedEntityEvents($embedded_projection, $event->getEmbeddedEntityEvents());
    }

    protected function onEmbeddedEntityModified(EntityInterface $projection, EmbeddedEntityModifiedEvent $event)
    {
        $embedded_projection_attr = $projection->getType()->getAttribute($event->getParentAttributeName());
        $embedded_projection_type = $this->getEmbeddedEntityTypeFor($projection, $event);

        $embedded_projections = $projection->getValue($embedded_projection_attr->getName());
        $projection_to_modify = null;
        foreach ($embedded_projections as $embedded_projection) {
            if ($embedded_projection->getIdentifier() === $event->getEmbeddedEntityIdentifier()) {
                $projection_to_modify = $embedded_projection;
            }
        }

        if ($projection_to_modify) {
            $embedded_projections->removeItem($projection_to_modify);
            $projection_to_modify = $embedded_projection_type->createEntity(
                array_merge($projection_to_modify->toArray(), $event->getData()),
                $projection
            );

            if ($embedded_projection_type instanceof ReferencedEntityTypeInterface) {
                $projection_to_modify = $this->mirrorReferencedValues($projection_to_modify);
            }

            $embedded_projections->insertAt($event->getPosition(), $projection_to_modify);
            $this->handleEmbeddedEntityEvents($projection_to_modify, $event->getEmbeddedEntityEvents());
        }
    }

    protected function onEmbeddedEntityRemoved(EntityInterface $projection, EmbeddedEntityRemovedEvent $event)
    {
        $projection_list = $projection->getValue($event->getParentAttributeName());
        $projection_to_remove = null;

        foreach ($projection_list as $embedded_projection) {
            if ($embedded_projection->getIdentifier() === $event->getEmbeddedEntityIdentifier()) {
                $projection_to_remove = $embedded_projection;
            }
        }

        if ($projection_to_remove) {
            $projection_list->removeItem($projection_to_remove);
        }
    }

    /**
     * Determine and mirror created or changed values for referenced projections
     */
    protected function mirrorReferencedValues(EntityReferenceInterface $projection)
    {
        $mirrored_attribute_map = $this->getMirroredAttributeMap($projection->getType());

        // Don't need to load a referenced entity if the mirrored attribute map is empty
        if ($mirrored_attribute_map->isEmpty()) {
            return $projection;
        }

        // Load the referenced projection to mirror values from
        $referenced_type = $this->projection_type_map->getByClassName(
            $projection->getType()->getReferencedTypeClass()
        );
        $referenced_identifier = $projection->getReferencedIdentifier();

        if ($referenced_identifier === $projection->getRoot()->getIdentifier()) {
            $referenced_projection = $projection->getRoot(); // self reference, no need to load
        } else {
            $referenced_projection = $this->loadReferencedProjection($referenced_type, $referenced_identifier);
            if (!$referenced_projection) {
                $this->logger->debug('[Zombie Alarm] Unable to load referenced projection: ' . $referenced_identifier);
                return $projection;
            }
        }

        // Add default attribute values
        $mirrored_values['@type'] = $projection->getType()->getPrefix();
        $mirrored_values['identifier'] = $projection->getIdentifier();
        $mirrored_values['referenced_identifier'] = $projection->getReferencedIdentifier();
        $mirrored_values = array_merge(
            $this->mirrorEntityValues($projection->getType(), $referenced_projection),
            $mirrored_values
        );

        return $projection->getType()->createEntity($mirrored_values, $projection->getParent());
    }

    /**
     * Recursively mirror values from the provided entity without loading embedded references
     */
    protected function mirrorEntityValues(EntityTypeInterface $reference_entity_type, EntityInterface $source_entity)
    {
        // Add default mirrored values
        $mirrored_values['@type'] = $source_entity->getType()->getPrefix();
        $mirrored_values['identifier'] = $source_entity->getIdentifier();
        if ($source_entity instanceof EntityReferenceInterface) {
            $mirrored_values['referenced_identifier'] = $source_entity->getReferencedIdentifier();
        }

        // iterate the source attributes map
        $reference_mirror_map = $this->getMirroredAttributeMap($reference_entity_type);
        $source_mirror_map = $source_entity->getType()->getAttributes();
        foreach ($source_mirror_map->getKeys() as $mirrored_attribute_name) {
            if ($reference_mirror_map->hasKey($mirrored_attribute_name)) {
                $mirrored_value = $source_entity->getValue($mirrored_attribute_name);
                if ($mirrored_value instanceof EntityList) {
                    foreach ($mirrored_value as $position => $mirrored_entity) {
                        $mirrored_entity_prefix = $mirrored_entity->getType()->getPrefix();
                        $reference_mirror_type = $reference_mirror_map->getItem($mirrored_attribute_name)
                            ->getEmbeddedEntityTypeMap()
                            ->getItem($mirrored_entity_prefix);
                        $mirrored_value->removeItem($mirrored_entity);
                        $mirrored_value->insertAt(
                            $position,
                            $reference_mirror_type->createEntity(
                                $this->mirrorEntityValues($reference_mirror_type, $mirrored_entity),
                                $mirrored_entity->getParent()
                            )
                        );
                    }
                }
                $mirrored_values[$mirrored_attribute_name] = $mirrored_value;
            }
        }

        return $mirrored_values;
    }

    protected function getMirroredAttributeMap(EntityTypeInterface $entity_type)
    {
        return $entity_type->getAttributes()->filter(
            function ($attribute) {
                return (bool)$attribute->getOption('mirrored', false) === true;
            }
        );
    }

    protected function loadProjection(AggregateRootEventInterface $event, $identifier = null)
    {
        return $this->getStorageReader($event)->read($identifier ?: $event->getAggregateRootIdentifier());
    }

    protected function loadReferencedProjection(EntityTypeInterface $referenced_type, $identifier)
    {
        $search_result = $this->getFinder($referenced_type)->getByIdentifier($identifier);
        if (!$search_result->hasResults()) {
            return null;
        }
        return $search_result->getFirstResult();
    }

    protected function getEmbeddedEntityTypeFor(EntityInterface $projection, EmbeddedEntityEventInterface $event)
    {
        $embed_attribute = $projection->getType()->getAttribute($event->getParentAttributeName());

        return $embed_attribute->getEmbeddedTypeByPrefix($event->getEmbeddedEntityType());
    }

    protected function getProjectionType(AggregateRootEventInterface $event)
    {
        $ar_type = $this->aggregate_root_type_map->getByClassName($event->getAggregateRootType());

        if (!$this->projection_type_map->hasKey($ar_type->getPrefix())) {
            throw new RuntimeError('Unable to resolve projection type for prefix: ' . $ar_type->getPrefix());
        }

        return $this->projection_type_map->getItem($ar_type->getPrefix());
    }

    protected function getStorageWriter(AggregateRootEventInterface $event)
    {
        $projection_type = $this->getProjectionType($event);
        return $this->getDataAccessComponent($this->getProjectionType($event), 'writer');
    }

    protected function getStorageReader(AggregateRootEventInterface $event)
    {
        return $this->getDataAccessComponent($this->getProjectionType($event), 'reader');
    }

    protected function getFinder(EntityTypeInterface $entity_type)
    {
        return $this->getDataAccessComponent($entity_type, 'finder');
    }

    protected function getDataAccessComponent(ProjectionTypeInterface $projection_type, $component = 'reader')
    {
        $default_component_name = sprintf(
            '%s::projection.standard::view_store::%s',
            $projection_type->getPrefix(),
            $component
        );
        $custom_component_option = $projection_type->getPrefix() . '.' . $component;

        switch ($component) {
            case 'finder':
                return $this->data_access_service->getFinder(
                    $this->config->get($custom_component_option, $default_component_name)
                );
                break;
            case 'reader':
                return $this->data_access_service->getStorageReader(
                    $this->config->get($custom_component_option, $default_component_name)
                );
                break;
            case 'writer':
                return $this->data_access_service->getStorageWriter(
                    $this->config->get($custom_component_option, $default_component_name)
                );
                break;
        }

        throw new RuntimeError('Invalid data access component name given: ' . $component);
    }
}
