<?php

return [
    'interfaces' => [
        'test.interface.v1.api' => [
            'MethodWithUrlPlaceholder' => [
                'method' => 'get',
                'uri' => '/v1/{name=message/**}',
                'placeholders' => [
                    'name' => [
                        'getName',
                    ],
                ],
            ],
            'MethodWithBody' => [
                'method' => 'post',
                'uri' => '/v1/{name=message/**}',
                'body' => '*',
                'placeholders' => [
                    'name' => [
                        'getName',
                    ],
                ],
            ],
            'MethodWithNestedMessageAsBody' => [
                'method' => 'post',
                'uri' => '/v1/{name=message/**}',
                'body' => 'nested_message',
                'placeholders' => [
                    'name' => [
                        'getName',
                    ],
                ],
            ],
            'MethodWithNestedUrlPlaceholder' => [
                'method' => 'get',
                'uri' => '/v1/{nested_message=nested/**}',
                'body' => '*',
                'placeholders' => [
                    'nested_message' => [
                        'getNestedMessage',
                        'getName',
                    ]
                ],
            ],
        ],
    ],
];
