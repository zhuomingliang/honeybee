<?php

namespace Honeybee\Tests\Projection\EventHandler;

use Honeybee\Tests\TestCase;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Honeybee\Model\Event\EmbeddedEntityEventList;
use Honeybee\Projection\ProjectionTypeMap;
use Honeybee\Projection\EventHandler\ProjectionUpdater;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\DataAccess\DataAccessService;
use Honeybee\Infrastructure\DataAccess\Finder\FinderInterface;
use Honeybee\Infrastructure\DataAccess\Finder\FinderResult;
use Honeybee\Infrastructure\DataAccess\Storage\Elasticsearch\Projection\ProjectionWriter;
use Honeybee\Infrastructure\DataAccess\Storage\Elasticsearch\Projection\ProjectionReader;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Game\GameType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\GameType as GameProjectionType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player\PlayerType as PlayerProjectionType;
use Workflux\Builder\XmlStateMachineBuilder;
use Psr\Log\NullLogger;

class ProjectionUpdaterTest extends TestCase
{
    protected $aggregate_root_type_map;

    protected $projection_type_map;

    public function setUp()
    {
        $state_machine = $this->getDefaultStateMachine();

        $game_aggregate_root_type = new GameType($state_machine);
        $this->aggregate_root_type_map = new AggregateRootTypeMap(
            [ $game_aggregate_root_type->getPrefix() => $game_aggregate_root_type ]
        );

        $game_projection_type = new GameProjectionType($state_machine);
        $player_projection_type = new PlayerProjectionType($state_machine);
        $this->projection_type_map = new ProjectionTypeMap(
            [
                $game_projection_type->getPrefix() => $game_projection_type,
                $player_projection_type->getPrefix() => $player_projection_type,
            ]
        );
    }

    /**
     * @dataProvider provideTestEvents
     */
    public function testHandleEvents(array $event, array $aggregate_root, array $references, array $expected)
    {
        //@todo check the invocation count expectation matchers are working
        $mock_finder_result = \Mockery::mock(FinderResult::CLASS);
        $mock_finder_result->shouldReceive('hasResults')->times(count($references))->andReturn(true);
        foreach ($references as $reference_state) {
            $projection = $this->projection_type_map
                ->getByEntityImplementor($reference_state['@type'])
                ->createEntity($reference_state);
            $mock_finder_result->shouldReceive('getFirstResult')->once()->andReturn($projection);
        }

        // build mock finder responses
        $mock_finder = \Mockery::mock(FinderInterface::CLASS);
        foreach ($event['embedded_entity_events'] as $embedded_entity_event) {
            if (isset($embedded_entity_event['data']['referenced_identifier'])) {
                $mock_finder->shouldReceive('getByIdentifier')
                    ->once()
                    ->with($embedded_entity_event['data']['referenced_identifier'])
                    ->andReturn($mock_finder_result);
            }
        }

        // prepare storage writer expectations
        $mock_storage_writer = \Mockery::mock(ProjectionWriter::CLASS);
        $mock_storage_writer->shouldReceive('write')->once()->with(\Mockery::on(
            function ($projection) use ($expected) {
                // dump arrays here if required for debugging
                return $projection->toArray() === $expected;
            }
        ));

        $mock_data_access_service = \Mockery::mock(DataAccessService::CLASS);
        $mock_data_access_service->shouldReceive('getFinder')->once()->andReturn($mock_finder);
        $mock_data_access_service->shouldReceive('getStorageWriter')->once()->andReturn($mock_storage_writer);

        // Set up expectations for aggregate root modification events
        if (!empty($aggregate_root)) {
            $aggregate_root = $this->projection_type_map
                ->getByEntityImplementor($aggregate_root['@type'])
                ->createEntity($aggregate_root);
            $mock_storage_reader = \Mockery::mock(ProjectionReader::CLASS);
            $mock_storage_reader->shouldReceive('read')
                ->with($aggregate_root->getIdentifier())
                ->andReturn($aggregate_root);
            $mock_data_access_service->shouldReceive('getStorageReader')->once()->andReturn($mock_storage_reader);
        }

        // prepare and test subject
        $projection_updater = new ProjectionUpdater(
            new ArrayConfig([]),
            new NullLogger,
            $mock_data_access_service,
            $this->projection_type_map,
            $this->aggregate_root_type_map
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
