<?php

/*
 *  Test cases are described by related flow chart images
 */
return [
    'event' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Task\GameModifiedEvent',
        'data' => [
            'title' => 'Quake 9'
        ],
        'aggregate_root_identifier' => 'honeybee.fixtures.game-a7cec777-d932-4bbd-8156-261138d3fe39-de_DE-1',
        'aggregate_root_type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Game\GameType',
        'embedded_entity_events' => [
            [
                '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\RemoveEmbeddedEntity\EmbeddedEntityRemovedEvent',
                'data' => [
                    'identifier' => '99d68357-595e-41f3-9675-b532a8ed968f',
                    'referenced_identifier' =>
                        'honeybee.fixtures.player-c9a1fd68-e6e5-462c-a544-c86f0812cf6c-de_DE-1'
                ],
                'position' => 0,
                'embedded_entity_identifier' => '99d68357-595e-41f3-9675-b532a8ed968f',
                'embedded_entity_type' => 'player',
                'parent_attribute_name' => 'players',
                'embedded_entity_events' => []
            ],
            [
                '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\EmbeddedEntityAddedEvent',
                'data' => [
                    'identifier' => 'ca8a5117-927a-4f94-8b0d-7b0be6196acf',
                    'referenced_identifier' =>
                        'honeybee.fixtures.player-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1'
                ],
                'position' => 0,
                'embedded_entity_identifier' => 'ca8a5117-927a-4f94-8b0d-7b0be6196acf',
                'embedded_entity_type' => 'player',
                'parent_attribute_name' => 'players',
                'embedded_entity_events' => []
            ],
            [
                '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\EmbeddedEntityAddedEvent',
                'data' => [
                    'identifier' => '5cf33cf1-554b-40be-98e7-ef7b4e98ec8c',
                    'attempts' => 5
                ],
                'position' => 0,
                'embedded_entity_identifier' => '5cf33cf1-554b-40be-98e7-ef7b4e98ec8c',
                'embedded_entity_type' => 'challenge',
                'parent_attribute_name' => 'challenges',
                'embedded_entity_events' => []
            ]
        ],
        'seq_number' => 3,
        'uuid' => 'a7cec777-d932-4bbd-8156-261138d3fe39',
        'iso_date' => '2016-04-28T10:53:53.530472+00:00',
        'metadata' => []
    ],
    'aggregate_root' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\GameType',
        'identifier' => 'honeybee.fixtures.game-a7cec777-d932-4bbd-8156-261138d3fe39-de_DE-1',
        'revision' => 3,
        'uuid' => 'a7cec777-d932-4bbd-8156-261138d3fe39',
        'short_id' => 0,
        'language' => 'de_DE',
        'version' => 1,
        'created_at' => '2016-04-28T10:53:53.530472+00:00',
        'modified_at' => '2016-04-28T10:53:53.530472+00:00',
        'workflow_state' => 'edit',
        'workflow_parameters' => [],
        'metadata' => [],
        'title' => 'Doom 3',
        'challenges' => [],
        'players' => [
            [
                '@type' => 'player',
                'identifier' => '99d68357-595e-41f3-9675-b532a8ed968f',
                'referenced_identifier' => 'honeybee.fixtures.player-c9a1fd68-e6e5-462c-a544-c86f0812cf6c-de_DE-1'
            ]
        ]
    ],
    'references' => [
        'player' => [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player\Player',
            'identifier' => 'honeybee.fixtures.player-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
            'revision' => 1,
            'uuid' => 'a726301d-dbae-4fb6-91e9-a19188a17e71',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-03-27T10:52:37.371793+00:00',
            'modified_at' => '2016-03-27T10:52:37.371793+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'name' => 'Player 1, No Profiles',
            'profiles' => [],
            'unmirrored_profiles' => []
        ]
    ],
    'expected' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\Game',
        'identifier' => 'honeybee.fixtures.game-a7cec777-d932-4bbd-8156-261138d3fe39-de_DE-1',
        'revision' => 3,
        'uuid' => 'a7cec777-d932-4bbd-8156-261138d3fe39',
        'short_id' => 0,
        'language' => 'de_DE',
        'version' => 1,
        'created_at' => '2016-04-28T10:53:53.530472+00:00',
        'modified_at' => '2016-04-28T10:53:53.530472+00:00',
        'workflow_state' => 'edit',
        'workflow_parameters' => [],
        'metadata' => [],
        'title' => 'Quake 9',
        'challenges' => [
            [
                '@type' => 'challenge',
                'identifier' => '5cf33cf1-554b-40be-98e7-ef7b4e98ec8c',
                'attempts' => 5
            ]
        ],
        'players' => [
            [
                '@type' => 'player',
                'identifier' => 'ca8a5117-927a-4f94-8b0d-7b0be6196acf',
                'referenced_identifier' => 'honeybee.fixtures.player-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
                'name' => 'Player 1, No Profiles',
                'profiles' => [],
                'unmirrored_profiles' => []
            ]
        ]
    ]
];
