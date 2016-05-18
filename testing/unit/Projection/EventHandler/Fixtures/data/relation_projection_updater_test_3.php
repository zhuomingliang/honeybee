<?php

return [
    'event' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Task\PlayerModifiedEvent',
        'data' => [
            'name' => 'Garry Kasparov'
        ],
        'aggregate_root_identifier' => 'honeybee.fixtures.player-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
        'aggregate_root_type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Player\PlayerType',
        'embedded_entity_events' => [
            [
                '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\ModifyEmbeddedEntity\EmbeddedEntityModifiedEvent',
                'data' => [
                    'identifier' => '99d68357-595e-41f3-9675-b532a8ed968f',
                    'alias' => 'New Profile Alias',
                    'tags' => [ 'new', 'tags' ]
                ],
                'position' => 1,
                'embedded_entity_identifier' => '99d68357-595e-41f3-9675-b532a8ed968f',
                'embedded_entity_type' => 'profile',
                'parent_attribute_name' => 'profiles',
                'embedded_entity_events' => []
            ]
        ],
        'seq_number' => 11,
        'uuid' => '44c4597c-f463-4916-a330-2db87ef36547',
        'iso_date' => '2016-05-28T10:52:37.371793+00:00',
        'metadata' => []
    ],
    'relations' => [
        [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\Game',
            'identifier' => 'honeybee.fixtures.game-49c5a3b7-8127-4169-8676-a9ebb5229142-de_DE-1',
            'revision' => 3,
            'uuid' => '49c5a3b7-8127-4169-8676-a9ebb5229142',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-04-28T10:52:35.349643+00:00',
            'modified_at' => '2016-04-28T10:52:35.349643+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'title' => 'Doom 4',
            'challenges' => [
                [
                    '@type' => 'challenge',
                    'identifier' => '5f337a59-44bd-4ad4-9b53-7512a231f0b3',
                    'attempts' => 5
                ]
            ],
            'players' => [
                [
                    '@type' => 'player',
                    'identifier' => '789e81f0-82fa-45ac-a4e4-705f20209442',
                    'referenced_identifier' => 'honeybee.fixtures.player-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
                    'name' => 'Anatoly Karpov',
                    'profiles' => [
                        [
                            '@type' => 'profile',
                            'identifier' => 'e00cc42b-a8ad-95f4-f691-d938f3e94225',
                            'alias' => 'Mock Profile',
                            'tags' => [ 'mock', 'profile' ],
                            'badges' => []
                        ],
                        [
                            '@type' => 'profile',
                            'identifier' => '99d68357-595e-41f3-9675-b532a8ed968f',
                            'alias' => 'Old Alias',
                            'tags' => [ 'old', 'tags' ],
                            'badges' => [
                                [
                                    '@type' => 'badge',
                                    'identifier' => '30efa6d8-792b-4fc8-95af-6fb2a048bcac',
                                    'award' => 'High Score'
                                ]
                            ]
                        ]
                    ],
                ],
                [
                    '@type' => 'player',
                    'identifier' => '96202e45-1574-49d4-a4f1-33ed2e6d2f57',
                    'referenced_identifier' => 'honeybee.fixtures.player-c9a1fd68-e6e5-462c-a544-c86f0812cf6c-de_DE-1',
                    'name' => 'Mr Bean',
                    'profiles' => []
                ]
            ]
        ]
    ],
    'expectations' => [
        [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\Game',
            'identifier' => 'honeybee.fixtures.game-49c5a3b7-8127-4169-8676-a9ebb5229142-de_DE-1',
            'revision' => 3,
            'uuid' => '49c5a3b7-8127-4169-8676-a9ebb5229142',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-04-28T10:52:35.349643+00:00',
            'modified_at' => '2016-04-28T10:52:35.349643+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'title' => 'Doom 4',
            'challenges' => [
                [
                    '@type' => 'challenge',
                    'identifier' => '5f337a59-44bd-4ad4-9b53-7512a231f0b3',
                    'attempts' => 5
                ]
            ],
            'players' => [
                [
                    '@type' => 'player',
                    'identifier' => '789e81f0-82fa-45ac-a4e4-705f20209442',
                    'referenced_identifier' => 'honeybee.fixtures.player-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
                    'name' => 'Garry Kasparov',
                    'profiles' => [
                        [
                            '@type' => 'profile',
                            'identifier' => 'e00cc42b-a8ad-95f4-f691-d938f3e94225',
                            'alias' => 'Mock Profile',
                            'tags' => [ 'mock', 'profile' ],
                            'badges' => []
                        ],
                        [
                            '@type' => 'profile',
                            'identifier' => '99d68357-595e-41f3-9675-b532a8ed968f',
                            'alias' => 'New Profile Alias',
                            'tags' => [ 'new', 'tags' ],
                            'badges' => [
                                [
                                    '@type' => 'badge',
                                    'identifier' => '30efa6d8-792b-4fc8-95af-6fb2a048bcac',
                                    'award' => 'High Score'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    '@type' => 'player',
                    'identifier' => '96202e45-1574-49d4-a4f1-33ed2e6d2f57',
                    'referenced_identifier' => 'honeybee.fixtures.player-c9a1fd68-e6e5-462c-a544-c86f0812cf6c-de_DE-1',
                    'name' => 'Mr Bean',
                    'profiles' => []
                ]
            ]
        ]
    ]
];
