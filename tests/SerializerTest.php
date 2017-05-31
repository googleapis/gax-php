<?php
/**
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\GAX\UnitTests;

use Google\Api\HttpRule;
use Google\GAX\Serializer;
use Google\Protobuf\Any;
use Google\Protobuf\Field;
use Google\Protobuf\FieldMask;
use Google\Protobuf\ListValue;
use Google\Protobuf\Struct;
use Google\Protobuf\Value;
use Google\Rpc\Status;

/**
 * @group core
 */
class SerializerTest extends \PHPUnit_Framework_TestCase
{
    private function backAndForth($message, $arrayStructure)
    {
        $serializer = new Serializer();
        $klass = get_class($message);

        $serializedMessage = $serializer->encodeMessage($message);
        $deserializedMessage = $serializer->decodeMessage(new $klass(), $serializedMessage);
        $this->assertEquals($arrayStructure, $serializedMessage);
        $this->assertEquals($message, $deserializedMessage);

        $deserializedStructure = $serializer->decodeMessage(new $klass(), $arrayStructure);
        $reserializedStructure = $serializer->encodeMessage($deserializedStructure);
        $this->assertEquals($message, $deserializedStructure);
        $this->assertEquals($arrayStructure, $reserializedStructure);
    }

    public function testStatusMessage()
    {
        $details = [new Any()];
        $message = new Status();
        $message->setMessage("message");
        $message->setCode(0);
        $message->setDetails($details);
        $this->backAndForth($message, [
            'message' => 'message',
            'code' => 0,
            'details' => [
                [
                    'typeUrl' => '',
                    'value' => '',
                ],
            ]
        ]);
    }

    public function testHttpRule()
    {
        $message = new HttpRule();
        $this->backAndForth($message, [
            'selector' => '',
            'body' => '',
            'additionalBindings' => [],
        ]);
    }

    public function testHttpRuleSetOneof()
    {
        $message = new HttpRule();
        $message->setPatch('');
        $this->backAndForth($message, [
            'selector' => '',
            'patch' => '',
            'body' => '',
            'additionalBindings' => [],
        ]);
    }

    public function testHttpRuleSetOneofToValue()
    {
        $message = new HttpRule();
        $message->setPatch('test');
        $this->backAndForth($message, [
            'selector' => '',
            'patch' => 'test',
            'body' => '',
            'additionalBindings' => [],
        ]);
    }

    public function testFieldMask()
    {
        $message = new FieldMask();
        $this->backAndForth($message, [
            'paths' => []
        ]);
    }

    public function testProperlyHandlesMessage()
    {
        $value = 'test';

        // Using this class because it contains maps, oneofs and structs
        $message = new \Google\Protobuf\Struct();

        $innerValue1 = new Value();
        $innerValue1->setStringValue($value);

        $innerValue2 = new Value();
        $innerValue2->setBoolValue(true);

        $structValue1 = new Value();
        $structValue1->setStringValue(strtoupper($value));
        $structValue2 = new Value();
        $structValue2->setStringValue($value);
        $labels = [
            strtoupper($value) => $structValue1,
            $value => $structValue2,
        ];
        $innerStruct = new Struct();
        $innerStruct->setFields($labels);
        $innerValue3 = new Value();
        $innerValue3->setStructValue($innerStruct);

        $innerValues = [$innerValue1, $innerValue2, $innerValue3];
        $listValue = new ListValue();
        $listValue->setValues($innerValues);
        $fieldValue = new Value();
        $fieldValue->setListValue($listValue);

        $fields = [
            'listField' => $fieldValue,
        ];
        $message->setFields($fields);

        $this->backAndForth($message, [
            'fields' => [
                'listField' => [
                    'listValue' => [
                        'values' => [
                            [
                                'stringValue' => $value,
                            ],
                            [
                                'boolValue' => true,
                            ],
                            [
                                'structValue' => [
                                    'fields' => [
                                        strtoupper($value) => [
                                            'stringValue' => strtoupper($value),
                                        ],
                                        $value => [
                                            'stringValue' => $value,
                                        ]
                                    ],
                                ],
                            ]
                        ]
                    ]
                ]
            ],
        ]);
    }
}
