<?php

return [
    'interfaces' => [
        'google.longrunning.Operations' => [
            'GetOperation' => [
                'method' => 'get',
                'uri' => '/v1/{name=operations/**}',
                'placeholders' => [
                    'name' => [
                        'getName',
                    ],
                ],
            ],
            'ListOperations' => [
                'method' => 'get',
                'uri' => '/v1/{name=operations}',
                'placeholders' => [
                    'name' => [
                        'getName',
                    ],
                ],
            ],
            'CancelOperation' => [
                'method' => 'post',
                'uri' => '/v1/{name=operations/**}:cancel',
                'body' => '*',
                'placeholders' => [
                    'name' => [
                        'getName',
                    ],
                ],
            ],
            'DeleteOperation' => [
                'method' => 'delete',
                'uri' => '/v1/{name=operations/**}',
                'placeholders' => [
                    'name' => [
                        'getName',
                    ],
                ],
            ],
        ],
    ],
];
