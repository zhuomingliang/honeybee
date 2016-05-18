<?php

return [
    'event' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Task\TeamModifiedEvent',
        'data' => [
            'name' => 'Burst City Rockers'
        ],
        'aggregate_root_identifier' => 'honeybee.fixtures.team-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
        'aggregate_root_type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Team\TeamType',
        'embedded_entity_events' => [],
        'seq_number' => 5,
        'uuid' => '44c4597c-f463-4916-a330-2db87ef36547',
        'iso_date' => '2016-05-28T10:52:37.371793+00:00',
        'metadata' => []
    ],
    'relations' => [
        [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player\Player',
            'identifier' => 'honeybee.fixtures.player-96202e45-1574-49d4-a4f1-33ed2e6d2f57-de_DE-1',
            'revision' => 3,
            'uuid' => '96202e45-1574-49d4-a4f1-33ed2e6d2f57',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-04-26T10:52:35.349643+00:00',
            'modified_at' => '2016-04-26T10:52:35.349643+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'name' => 'Mr Bean',
            'profiles' => [
                [
                    '@type' => 'profile',
                    'identifier' => '6c469af2-f60a-4bd9-b220-822a377f033e',
                    'alias' => 'mockprofile1',
                    'tags' => [ 'mock', 'profile', 'one' ],
                    'teams' => [
                        [
                            '@type' => 'team',
                            'identifier' => '704856e1-28a3-4069-8055-4ff4fd2f3b83',
                            'referenced_identifier' =>
                                'honeybee.fixtures.team-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
                            'name' => 'Super Clan'
                        ]
                    ],
                    'badges' => [
                        [
                            '@type' => 'badge',
                            'identifier' => '30efa6d8-792b-4fc8-95af-6fb2a048bcac',
                            'award' => 'High Score'
                        ]
                    ]
                ]
            ],
            'simple_profiles' => [
                [
                    '@type' => 'profile',
                    'identifier' => 'b46caba3-19ef-4bdf-b5ab-37f925485005',
                    'alias' => 'hiddenprofile1',
                    'tags' => [ 'hidden', 'player', 'profile', 'one' ],
                    'teams' => [
                        [
                            '@type' => 'team',
                            'identifier' => '8f5acbea-ad9a-4cd3-86f6-8fd4de2e734a',
                            'referenced_identifier' =>
                                'honeybee.fixtures.team-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
                            'name' => 'Super Clan'
                        ]
                    ],
                    'badges' => []
                ]
            ]
        ],
        [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player\Player',
            'identifier' => 'honeybee.fixtures.player-c9a1fd68-e6e5-462c-a544-c86f0812cf6c-de_DE-1',
            'revision' => 5,
            'uuid' => 'c9a1fd68-e6e5-462c-a544-c86f0812cf6c',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-04-25T10:52:35.349643+00:00',
            'modified_at' => '2016-04-25T10:52:35.349643+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'name' => 'Idiot Portal',
            'profiles' => [
                [
                    '@type' => 'profile',
                    'identifier' => '193bb73b-368e-4145-b322-4a6649cebb55',
                    'alias' => 'mockprofile1',
                    'tags' => [ 'mock', 'profile', 'one' ],
                    'teams' => [
                        [
                            '@type' => 'team',
                            'identifier' => '28347562-927b-449d-a585-a80cf94f730b',
                            'referenced_identifier' =>
                                'honeybee.fixtures.team-349d6781-205f-42b7-932f-43b647ac2468-de_DE-1',
                            'name' => 'Carnivorous Vegans'
                        ],
                        [
                            '@type' => 'team',
                            'identifier' => '96202e45-1574-49d4-a4f1-33ed2e6d2f57',
                            'referenced_identifier' =>
                                'honeybee.fixtures.team-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
                            'name' => 'Super Clan'
                        ]
                    ],
                    'badges' => [
                        [
                            '@type' => 'badge',
                            'identifier' => '30efa6d8-792b-4fc8-95af-6fb2a048bcac',
                            'award' => 'High Score'
                        ]
                    ],
                ],
                [
                    '@type' => 'profile',
                    'identifier' => '349d6781-205f-42b7-932f-43b647ac2468',
                    'alias' => 'mockprofile2',
                    'tags' => [ 'mock', 'profile', 'two' ],
                    'teams' => [
                        [
                            '@type' => 'team',
                            'identifier' => '4e17d22b-028a-436f-9adf-06185712cac2',
                            'referenced_identifier' =>
                                'honeybee.fixtures.team-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
                            'name' => 'Super Clan'
                        ]
                    ],
                    'badges' => []
                ],
            ],
            'simple_profiles' => []
        ]
    ],
    'expectations' => [
        [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player\Player',
            'identifier' => 'honeybee.fixtures.player-96202e45-1574-49d4-a4f1-33ed2e6d2f57-de_DE-1',
            'revision' => 3,
            'uuid' => '96202e45-1574-49d4-a4f1-33ed2e6d2f57',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-04-26T10:52:35.349643+00:00',
            'modified_at' => '2016-04-26T10:52:35.349643+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'name' => 'Mr Bean',
            'profiles' => [
                [
                    '@type' => 'profile',
                    'identifier' => '6c469af2-f60a-4bd9-b220-822a377f033e',
                    'alias' => 'mockprofile1',
                    'tags' => [ 'mock', 'profile', 'one' ],
                    'teams' => [
                        [
                            '@type' => 'team',
                            'identifier' => '704856e1-28a3-4069-8055-4ff4fd2f3b83',
                            'referenced_identifier' =>
                                'honeybee.fixtures.team-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
                            'name' => 'Burst City Rockers'
                        ]
                    ],
                    'badges' => [
                        [
                            '@type' => 'badge',
                            'identifier' => '30efa6d8-792b-4fc8-95af-6fb2a048bcac',
                            'award' => 'High Score'
                        ]
                    ],
                ]
            ],
            'simple_profiles' => [
                [
                    '@type' => 'profile',
                    'identifier' => 'b46caba3-19ef-4bdf-b5ab-37f925485005',
                    'alias' => 'hiddenprofile1',
                    'tags' => [ 'hidden', 'player', 'profile', 'one' ],
                    'teams' => [
                        [
                            '@type' => 'team',
                            'identifier' => '8f5acbea-ad9a-4cd3-86f6-8fd4de2e734a',
                            'referenced_identifier' =>
                                'honeybee.fixtures.team-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
                            'name' => 'Burst City Rockers'
                        ]
                    ],
                    'badges' => []
                ]
            ]
        ],
        [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player\Player',
            'identifier' => 'honeybee.fixtures.player-c9a1fd68-e6e5-462c-a544-c86f0812cf6c-de_DE-1',
            'revision' => 5,
            'uuid' => 'c9a1fd68-e6e5-462c-a544-c86f0812cf6c',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-04-25T10:52:35.349643+00:00',
            'modified_at' => '2016-04-25T10:52:35.349643+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'name' => 'Idiot Portal',
            'profiles' => [
                [
                    '@type' => 'profile',
                    'identifier' => '193bb73b-368e-4145-b322-4a6649cebb55',
                    'alias' => 'mockprofile1',
                    'tags' => [ 'mock', 'profile', 'one' ],
                    'teams' => [
                        [
                            '@type' => 'team',
                            'identifier' => '28347562-927b-449d-a585-a80cf94f730b',
                            'referenced_identifier' =>
                            'honeybee.fixtures.team-349d6781-205f-42b7-932f-43b647ac2468-de_DE-1',
                            'name' => 'Carnivorous Vegans'
                        ],
                        [
                            '@type' => 'team',
                            'identifier' => '96202e45-1574-49d4-a4f1-33ed2e6d2f57',
                            'referenced_identifier' =>
                            'honeybee.fixtures.team-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
                            'name' => 'Burst City Rockers'
                        ]
                    ],
                    'badges' => [
                        [
                            '@type' => 'badge',
                            'identifier' => '30efa6d8-792b-4fc8-95af-6fb2a048bcac',
                            'award' => 'High Score'
                        ]
                    ]
                ],
                [
                    '@type' => 'profile',
                    'identifier' => '349d6781-205f-42b7-932f-43b647ac2468',
                    'alias' => 'mockprofile2',
                    'tags' => [ 'mock', 'profile', 'two' ],
                    'teams' => [
                        [
                            '@type' => 'team',
                            'identifier' => '4e17d22b-028a-436f-9adf-06185712cac2',
                            'referenced_identifier' =>
                                'honeybee.fixtures.team-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
                            'name' => 'Burst City Rockers'
                        ]
                    ],
                    'badges' => [],
                ]
            ],
            'simple_profiles' => []
        ]
    ]
];
