<?php

return [
    'interfaces' => [
        'test.interface.v1.api' => [
            'MethodWithUrlPlaceholder' => [
                'method' => 'get',
                'uriTemplate' => '/v1/{name=message/**}',
                'placeholders' => [
                    'name' => [
                        'getters' => [
                            'getName'
                        ]
                    ],
                ],
            ],
            'MethodWithBody' => [
                'method' => 'post',
                'uriTemplate' => '/v1/{name=message/**}',
                'body' => '*',
                'placeholders' => [
                    'name' => [
                        'getters' => [
                            'getName'
                        ]
                    ],
                ],
            ],
            'MethodWithNestedMessageAsBody' => [
                'method' => 'post',
                'uriTemplate' => '/v1/{name=message/**}',
                'body' => 'nested_message',
                'placeholders' => [
                    'name' => [
                        'getters' => [
                            'getName'
                        ]
                    ],
                ],
            ],
            'MethodWithNestedUrlPlaceholder' => [
                'method' => 'get',
                'uriTemplate' => '/v1/{nested_message=nested/**}',
                'body' => '*',
                'placeholders' => [
                    'nested_message' => [
                        'getters' => [
                            'getNestedMessage',
                            'getName'
                        ]
                    ]
                ],
            ],
        ],
    ],
];
