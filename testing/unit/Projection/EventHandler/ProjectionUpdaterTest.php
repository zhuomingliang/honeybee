<?php

namespace Honeybee\Tests\Projection\EventHandler;

use Honeybee\Tests\TestCase;
use Honeybee\Infrastructure\Event\Event;
use Honeybee\Projection\ProjectionTypeMap;
use Honeybee\Projection\EventHandler\ProjectionUpdater;
use Honeybee\Model\Event\EmbeddedEntityEventList;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Game\GameType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\GameType as GameProjectionType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player\PlayerType as PlayerProjectionType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team\TeamType as TeamProjectionType;
use Psr\Log\NullLogger;
use Workflux\Builder\XmlStateMachineBuilder;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\DataAccess\DataAccessService;
use Honeybee\Infrastructure\DataAccess\Finder\FinderInterface;
use Honeybee\Infrastructure\DataAccess\Finder\FinderResult;
use Honeybee\Infrastructure\DataAccess\Storage\Elasticsearch\Projection\ProjectionWriter;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\Game;

class ProjectionUpdaterTest extends TestCase
{
    protected $aggregate_root_type_map;

    protected $projection_type_map;

    public function setUp()
    {
        $game_type = new GameType($this->getDefaultStateMachine());
        $this->aggregate_root_type_map = new AggregateRootTypeMap(
            [ $game_type->getPrefix() => $game_type ]
        );

        $game_projection_type = new GameProjectionType($this->getDefaultStateMachine());
        $player_projection_type = new PlayerProjectionType($this->getDefaultStateMachine());
        $team_projection_type = new TeamProjectionType($this->getDefaultStateMachine());
        $this->projection_type_map = new ProjectionTypeMap(
            [
                $game_projection_type->getPrefix() => $game_projection_type,
                $player_projection_type->getPrefix() => $player_projection_type,
                $team_projection_type->getPrefix() => $team_projection_type,
            ]
        );
    }

    /**
     * @dataProvider provideRelationEvents
     */
    public function testHandleEvent(array $event, array $aggregate_root, array $references, array $expected)
    {
        $event = $this->buildEvent($event);

        $projection_keys = $this->projection_type_map->getKeys();
        $projections = [];
        foreach ($references as $reference_name => $reference_state) {
            $projections[] = $this->projection_type_map[$reference_state['@type']]->createEntity($reference_state);
        }

        $mock_finder_result = \Mockery::mock(FinderResult::CLASS);
        $mock_finder_result->shouldReceive('hasResults')->twice()->andReturn(true);
        foreach ($projections as $projection) {
            $mock_finder_result->shouldReceive('getFirstResult')->once()->andReturn($projection);
        }

        $mock_finder = \Mockery::mock(FinderInterface::CLASS);
        $mock_finder->shouldReceive('getByIdentifier')
            ->once()
            ->with('honeybee.fixtures.player-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1')
            ->andReturn($mock_finder_result);

        $mock_storage_writer = \Mockery::mock(ProjectionWriter::CLASS);
        $mock_storage_writer->shouldReceive('write')->once()->with(\Mockery::on(
            function ($argument) use ($expected) {
                return $argument->toArray() === $expected;
            }
        ));

        $mock_data_access_service = \Mockery::mock(DataAccessService::CLASS);
        $mock_data_access_service->shouldReceive('getFinder')->once()->andReturn($mock_finder);
        $mock_data_access_service->shouldReceive('getStorageWriter')->once()->andReturn($mock_storage_writer);

        $projection_updater = new ProjectionUpdater(
            new ArrayConfig([]),
            new NullLogger,
            $mock_data_access_service,
            $this->projection_type_map,
            $this->aggregate_root_type_map
        );

        $projection_updater->handleEvent($event);
    }

    // ------------------------------ helpers ------------------------------

    public function provideRelationEvents()
    {
        return include __DIR__ . '/Fixtures/relation_events.php';
    }

    protected function buildEvent(array $event_state)
    {
        $event_class = $event_state[Event::OBJECT_TYPE];
        $embedded_entity_events = new EmbeddedEntityEventList;
        foreach ($event_state['embedded_entity_events'] as $embedded_event_state) {
            $embedded_entity_events->push($this->buildEvent($embedded_event_state));
        }
        $event_state['embedded_entity_events'] = $embedded_entity_events;
        return new $event_class($event_state);
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