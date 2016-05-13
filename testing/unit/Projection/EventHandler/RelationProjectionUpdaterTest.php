<?php

namespace Honeybee\Tests\Projection\EventHandler;

use Honeybee\Tests\TestCase;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Honeybee\Model\Event\EmbeddedEntityEventList;
use Honeybee\Projection\ProjectionTypeMap;
use Honeybee\Projection\EventHandler\RelationProjectionUpdater;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceMap;
use Honeybee\Infrastructure\DataAccess\Finder\FinderResult;
use Honeybee\Infrastructure\DataAccess\Storage\StorageWriterMap;
use Honeybee\Infrastructure\DataAccess\Storage\Elasticsearch\Projection\ProjectionWriter;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceInterface;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Player\PlayerType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Team\TeamType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\GameType as GameProjectionType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player\PlayerType as PlayerProjectionType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team\TeamType as TeamProjectionType;
use Workflux\Builder\XmlStateMachineBuilder;
use Psr\Log\NullLogger;

class RelationProjectionUpdaterTest extends TestCase
{
    protected $aggregate_root_type_map;

    protected $projection_type_map;

    public function setUp()
    {
        $state_machine = $this->getDefaultStateMachine();

        $player_aggregate_root_type = new PlayerType($state_machine);
        $team_aggregate_root_type = new TeamType($state_machine);
        $this->aggregate_root_type_map = new AggregateRootTypeMap(
            [
                $player_aggregate_root_type->getPrefix() => $player_aggregate_root_type,
                $team_aggregate_root_type->getPrefix() => $team_aggregate_root_type
            ]
        );

        $game_projection_type = new GameProjectionType($state_machine);
        $player_projection_type = new PlayerProjectionType($state_machine);
        $team_projection_type = new TeamProjectionType($state_machine);
        $this->projection_type_map = new ProjectionTypeMap(
            [
                $game_projection_type->getPrefix() => $game_projection_type,
                $player_projection_type->getPrefix() => $player_projection_type,
                $team_projection_type->getPrefix() => $team_projection_type,
            ]
        );
    }

    /**
     * @dataProvider provideTestEvents
     */
    public function testHandleEvents(array $event, array $relations, array $expectations)
    {
        //@todo check the invocation count expectation matchers are working
        $mock_query_service_map = new QueryServiceMap;
        $mock_storage_writer_map = new StorageWriterMap;

        // build projection finder results
        foreach ($relations as $relation) {
            $projection_type = $this->projection_type_map->getByEntityImplementor($relation['@type']);
            $related_projections[] = $projection_type->createEntity($relation);
        }

        // prepare mock query responses
        $projection_type_prefix = $projection_type->getPrefix();
        $mock_query_service = \Mockery::mock(QueryServiceInterface::CLASS);
        $mock_query_service->shouldReceive('find')
            ->with(\Mockery::on(
                function ($query) use ($event) {
                    $filter_criteria_list = $query->toArray()['filter_criteria_list'];
                    foreach ($filter_criteria_list as $filter_criteria) {
                        if (strpos($filter_criteria['attribute_path'], 'referenced_identifier') !== false
                            && $filter_criteria['comparison']['comparand'] !== $event['aggregate_root_identifier']
                        ) {
                            return false;
                        }
                    }
                    return true;
                }
            ))
            ->times(count($related_projections))
            ->andReturn(new FinderResult($related_projections));
        $mock_query_service_map->setItem($projection_type_prefix . '::query_service', $mock_query_service);

        // prepare storage writer expectations
        $mock_storage_writer = \Mockery::mock(ProjectionWriter::CLASS);
        foreach ($expectations as $expected) {
            $mock_storage_writer->shouldReceive('write')
                ->once()
                ->with(\Mockery::on(
                    function ($projection) use ($expected) {
                        // dump arrays here if required for debugging
                        return $projection->toArray() === $expected;
                    }
                ));
        }
        $mock_storage_writer_map->setItem(
            $projection_type_prefix . '::projection.standard::view_store::writer',
            $mock_storage_writer
        );

        // prepare and test subject
        $relation_projection_updater = new RelationProjectionUpdater(
            new ArrayConfig([ 'projection_type' => $projection_type_prefix ]),
            new NullLogger,
            $mock_storage_writer_map,
            $mock_query_service_map,
            $this->projection_type_map,
            $this->aggregate_root_type_map
        );

        $event = $this->buildEvent($event);
        $relation_projection_updater->handleEvent($event);
    }

    // ------------------------------ helpers ------------------------------

    public function provideTestEvents()
    {
        $tests = [];
        foreach (glob(__DIR__ . '/Fixtures/data/relation_projection_updater*.php') as $filename) {
            $tests[] = include $filename;
        }
        return $tests;
    }

    protected function buildEvent(array $event_state)
    {
        $event_type_class = $event_state['@type'];
        $embedded_entity_events = new EmbeddedEntityEventList;
        foreach ($event_state['embedded_entity_events'] as $embedded_event_state) {
            $embedded_entity_events->push($this->buildEvent($embedded_event_state));
        }
        $event_state['embedded_entity_events'] = $embedded_entity_events;
        return new $event_type_class($event_state);
    }

    protected function getDefaultStateMachine()
    {
        $workflows_file_path = __DIR__ . '/Fixtures/Model/workflows.xml';
        $workflow_builder = new XmlStateMachineBuilder(
            [
                'name' => 'game_workflow_default',
                'state_machine_definition' => $workflows_file_path
            ]
        );

        return $workflow_builder->build();
    }
}
