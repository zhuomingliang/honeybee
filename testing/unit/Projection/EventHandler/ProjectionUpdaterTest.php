<?php

namespace Honeybee\Tests\Projection\EventHandler;

use Honeybee\Tests\TestCase;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Honeybee\Model\Event\EmbeddedEntityEventList;
use Honeybee\Projection\ProjectionTypeMap;
use Honeybee\Projection\ProjectionInterface;
use Honeybee\Projection\ProjectionUpdatedEvent;
use Honeybee\Projection\EventHandler\ProjectionUpdater;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Event\Bus\EventBus;
use Honeybee\Infrastructure\DataAccess\DataAccessService;
use Honeybee\Infrastructure\DataAccess\Finder\FinderInterface;
use Honeybee\Infrastructure\DataAccess\Finder\FinderResult;
use Honeybee\Infrastructure\DataAccess\Storage\Elasticsearch\Projection\ProjectionWriter;
use Honeybee\Infrastructure\DataAccess\Storage\Elasticsearch\Projection\ProjectionReader;
use Honeybee\Infrastructure\DataAccess\Query\QueryInterface;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceMap;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceInterface;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Game\GameType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Team\TeamType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\GameType as GameProjectionType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player\PlayerType as PlayerProjectionType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team\TeamType as TeamProjectionType;
use Workflux\Builder\XmlStateMachineBuilder;
use Psr\Log\NullLogger;
use Mockery as M;

class ProjectionUpdaterTest extends TestCase
{
    protected $aggregate_root_type_map;

    protected $projection_type_map;

    public function setUp()
    {
        $state_machine = $this->getDefaultStateMachine();

        $game_aggregate_root_type = new GameType($state_machine);
        $team_aggregate_root_type = new TeamType($state_machine);
        $this->aggregate_root_type_map = new AggregateRootTypeMap(
            [
                $game_aggregate_root_type->getPrefix() => $game_aggregate_root_type,
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
                $team_projection_type->getPrefix() => $team_projection_type
            ]
        );
    }

    /**
     * @dataProvider provideTestEvents
     */
    public function testHandleEvents(
        array $event,
        array $aggregate_root,
        array $parent_node,
        array $query,
        array $projections,
        array $expectations
    ) {
        $mock_finder_result = M::mock(FinderResult::CLASS);

        //different handling when using a query
        if (empty($query)) {
            $mock_finder_result->shouldReceive('hasResults')->times(count($projections))->andReturn(true);
            foreach ($projections as $reference_state) {
                $projection = $this->projection_type_map
                    ->getByEntityImplementor($reference_state['@type'])
                    ->createEntity($reference_state);
                $mock_finder_result->shouldReceive('getFirstResult')->once()->andReturn($projection);
            }
        }

        // build mock finder responses
        $mock_finder = M::mock(FinderInterface::CLASS);
        foreach ($event['embedded_entity_events'] as $embedded_entity_event) {
            if (isset($embedded_entity_event['data']['referenced_identifier'])
                && strpos($embedded_entity_event['@type'], 'Removed') === false
            ) {
                $mock_finder->shouldReceive('getByIdentifier')
                    ->once()
                    ->with($embedded_entity_event['data']['referenced_identifier'])
                    ->andReturn($mock_finder_result);
            }
        }

        // prepare storage writer expectations
        $mock_storage_writer = M::mock(ProjectionWriter::CLASS);
        foreach ($expectations as $expectation) {
            $mock_storage_writer->shouldReceive('write')
                ->once()
                ->with(M::on(
                    function (ProjectionInterface $projection) use ($expectation) {
                        $this->assertEquals($expectation, $projection->toArray());
                        return true;
                    }
                ));
        }

        $mock_event_bus = M::mock(EventBus::CLASS);
        foreach ($expectations as $expectation) {
            $mock_event_bus->shouldReceive('distribute')
                ->once()
                ->with('honeybee.events.infrastructure', M::on(
                    function (ProjectionUpdatedEvent $update_event) use ($expectation) {
                        $this->assertEquals($expectation['identifier'], $update_event->getProjectionIdentifier());
                        $this->assertEquals($expectation['@type'] . 'Type', $update_event->getProjectionType());
                        $this->assertEquals($expectation, $update_event->getData());
                        return true;
                    }
                ))
                ->andReturnNull();
        }

        $mock_data_access_service = M::mock(DataAccessService::CLASS);
        $mock_data_access_service->shouldReceive('getStorageWriter')
            ->times(count($expectations))
            ->andReturn($mock_storage_writer);

        // Set up expectations for aggregate root modification events
        $mock_storage_reader = M::mock(ProjectionReader::CLASS);
        if (!empty($aggregate_root)) {
            $aggregate_root = $this->projection_type_map
                ->getByEntityImplementor($aggregate_root['@type'])
                ->createEntity($aggregate_root);
            $mock_storage_reader->shouldReceive('read')
                ->once()
                ->with($aggregate_root->getIdentifier())
                ->andReturn($aggregate_root);
            $mock_data_access_service->shouldReceive('getStorageReader')
                ->once()
                ->with($aggregate_root->getType()->getPrefix() . '::projection.standard::view_store::reader')
                ->andReturn($mock_storage_reader);
        }

        // Setup expectations for node moved events
        if (!empty($parent_node)) {
            $parent_node = $this->projection_type_map
                ->getByEntityImplementor($parent_node['@type'])
                ->createEntity($parent_node);
            $mock_storage_reader->shouldReceive('read')
                ->once()
                ->with($parent_node->getIdentifier())
                ->andReturn($parent_node);
            $mock_data_access_service->shouldReceive('getStorageReader')
                ->once()
                ->with($parent_node->getType()->getPrefix() . '::projection.standard::view_store::reader')
                ->andReturn($mock_storage_reader);
        }

        // build projection finder results
        $mock_query_service_map = M::mock(QueryServiceMap::CLASS);
        if (!empty($query)) {
            foreach ($projections as $projection) {
                $projection_type = $this->projection_type_map->getByEntityImplementor($projection['@type']);
                $projection_type_prefix = $projection_type->getPrefix();
                $related_projections[] = $projection_type->createEntity($projection);
            }
            $mock_query_service = M::mock(QueryServiceInterface::CLASS);
            $mock_finder_result->shouldReceive('getResults')->once()->withNoArgs()->andReturn($related_projections);
            $mock_query_service_map->shouldReceive('getItem')
                ->once()
                ->with($projection_type_prefix . '::query_service')
                ->andReturn($mock_query_service);
            $mock_query_service->shouldReceive('find')
                ->once()
                ->with(M::on(
                    function (QueryInterface $search_query) use ($query) {
                        $this->assertEquals($query, $search_query->toArray());
                        return true;
                    }
                ))
                ->andReturn($mock_finder_result);
        } else {
            $mock_data_access_service->shouldReceive('getFinder')
                ->times(count($projections))
                ->andReturn($mock_finder);
            $mock_query_service_map->shouldNotReceive('getItem');
        }

        // prepare and test subject
        $projection_updater = new ProjectionUpdater(
            new ArrayConfig([]),
            new NullLogger,
            $mock_data_access_service,
            $mock_query_service_map,
            $this->projection_type_map,
            $this->aggregate_root_type_map,
            $mock_event_bus
        );

        $event = $this->buildEvent($event);
        $projection_updater->handleEvent($event);
    }

    // ------------------------------ helpers ------------------------------

    public function provideTestEvents()
    {
        $tests = [];
        foreach (glob(__DIR__ . '/Fixtures/data/projection_updater*.php') as $filename) {
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
