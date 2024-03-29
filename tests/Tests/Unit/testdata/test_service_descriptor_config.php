<?php

return [
    'interfaces' => [
        'test.interface.v1.api' => [
            'PageStreamingMethod' => [
                'pageStreaming' => [
                    'requestPageTokenGetMethod' => 'getPageToken',
                    'requestPageTokenSetMethod' => 'setPageToken',
                    'requestPageSizeGetMethod' => 'getPageSize',
                    'requestPageSizeSetMethod' => 'setPageSize',
                    'responsePageTokenGetMethod' => 'getNextPageToken',
                ],
                'autoPopulatedFields' => [
                    'pageToken' => \Google\Api\FieldInfo\Format::UUID4,
                ]
            ],
            'templateMap' => [
                'project' => 'projects/{project}',
                'location' => 'projects/{project}/locations/{location}',
                'archive' => 'archives/{archive}',
                'book' => 'archives/{archive}/books/{book}',
            ],
        ],
    ],
];
