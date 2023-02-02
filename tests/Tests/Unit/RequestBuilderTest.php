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

namespace Google\ApiCore\Tests\Unit;

use Google\ApiCore\RequestBuilder;
use Google\ApiCore\Testing\MockRequestBody;
use Google\ApiCore\ValidationException;
use Google\Protobuf\BytesValue;
use Google\Protobuf\Duration;
use Google\Protobuf\FieldMask;
use Google\Protobuf\Int64Value;
use Google\Protobuf\ListValue;
use Google\Protobuf\StringValue;
use Google\Protobuf\Struct;
use Google\Protobuf\Timestamp;
use Google\Protobuf\Value;
use GuzzleHttp\Psr7\Query;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * @group core
 */
class RequestBuilderTest extends TestCase
{
    private $builder;
    private $numericEnumsBuilder;

    const SERVICE_NAME = 'test.interface.v1.api';

    public function set_up()
    {
        $this->builder = new RequestBuilder(
            'www.example.com',
            __DIR__ . '/testdata/test_service_rest_client_config.php'
        );
        $this->numericEnumsBuilder = new RequestBuilder(
            'www.example.com',
            __DIR__ . '/testdata/test_numeric_enums_rest_client_config.php'
        );
    }

    public function testMethodWithUrlPlaceholder()
    {
        $message = new MockRequestBody();
        $message->setName('message/foo');

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithUrlPlaceholder', $message);
        $uri = $request->getUri();

        $this->assertEmpty($uri->getQuery());
        $this->assertEmpty((string) $request->getBody());
        $this->assertSame('/v1/message/foo', $uri->getPath());
    }

    public function testMethodWithBody()
    {
        $message = new MockRequestBody();
        $message->setName('message/foo');
        $nestedMessage = new MockRequestBody();
        $nestedMessage->setName('nested/foo');
        $message->setNestedMessage($nestedMessage);

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithBodyAndUrlPlaceholder', $message);
        $uri = $request->getUri();

        $this->assertEmpty($uri->getQuery());
        $this->assertSame('/v1/message/foo', $uri->getPath());
        $this->assertEquals(
            ['name' => 'message/foo', 'nestedMessage' => ['name' => 'nested/foo']],
            json_decode($request->getBody(), true)
        );
    }

    public function testMethodWithNestedMessageAsBody()
    {
        $message = new MockRequestBody();
        $message->setName('message/foo');
        $nestedMessage = new MockRequestBody();
        $nestedMessage->setName('nested/foo');
        $message->setNestedMessage($nestedMessage);

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithNestedMessageAsBody', $message);
        $uri = $request->getUri();

        $this->assertEmpty($uri->getQuery());
        $this->assertSame('/v1/message/foo', $uri->getPath());
        $this->assertEquals(
            ['name' => 'nested/foo'],
            json_decode($request->getBody(), true)
        );
    }

    public function testMethodWithScalarBody()
    {
        $message = new MockRequestBody();
        $message->setName('foo');

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithScalarBody', $message);

        $this->assertEquals(
            '"foo"',
            (string) $request->getBody()
        );
    }

    public function testMethodWithEmptyMessageInBody()
    {
        $message = new MockRequestBody();
        $nestedMessage = new MockRequestBody();
        $message->setNestedMessage($nestedMessage);

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithBody', $message);

        $this->assertEquals(
            '{"nestedMessage":{}}',
            $request->getBody()
        );
    }

    public function testMethodWithEmptyMessageInNestedMessageBody()
    {
        $message = new MockRequestBody();
        $message->setName('message/foo');
        $nestedMessage = new MockRequestBody();
        $message->setNestedMessage($nestedMessage);
        $emptyMessage = new MockRequestBody();
        $nestedMessage->setNestedMessage($emptyMessage);


        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithNestedMessageAsBody', $message);

        $this->assertEquals(
            '{"nestedMessage":{}}',
            $request->getBody()
        );
    }

    public function testMethodWithNestedUrlPlaceholder()
    {
        $message = new MockRequestBody();
        $message->setName('message/foo');
        $nestedMessage = new MockRequestBody();
        $nestedMessage->setName('nested/foo');
        $message->setNestedMessage($nestedMessage);

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithNestedUrlPlaceholder', $message);
        $uri = $request->getUri();

        $this->assertEmpty($uri->getQuery());
        $this->assertSame('/v1/nested/foo', $uri->getPath());
        $this->assertEquals(
            ['name' => 'message/foo', 'nestedMessage' => ['name' => 'nested/foo']],
            json_decode($request->getBody(), true)
        );
    }

    public function testMethodWithUrlRepeatedField()
    {
        $message = new MockRequestBody();
        $message->setName('message/foo');
        $message->setRepeatedField(['bar1', 'bar2']);

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithUrlPlaceholder', $message);
        $uri = $request->getUri();

        $this->assertEmpty((string) $request->getBody());
        $this->assertSame('/v1/message/foo', $uri->getPath());
        $this->assertSame('repeatedField=bar1&repeatedField=bar2', $uri->getQuery());
    }

    public function testMethodWithHeaders()
    {
        $message = new MockRequestBody();
        $message->setName('message/foo');

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithUrlPlaceholder', $message, [
            'header1' => 'value1',
            'header2' => 'value2'
        ]);

        $this->assertSame('value1', $request->getHeaderLine('header1'));
        $this->assertSame('value2', $request->getHeaderLine('header2'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function testMethodWithColon()
    {
        $message = new MockRequestBody();
        $message->setName('message/foo');

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithColonInUrl', $message);
        $uri = $request->getUri();

        $this->assertEmpty($uri->getQuery());
        $this->assertSame('/v1/message/foo:action', $uri->getPath());
    }

    public function testMethodWithMultipleWildcardsAndColonInUrl()
    {
        $message = new MockRequestBody();
        $message->setName('message/foo');
        $message->setNumber(10);

        $request = $this->builder->build(
            self::SERVICE_NAME . '/MethodWithMultipleWildcardsAndColonInUrl',
            $message
        );
        $uri = $request->getUri();

        $this->assertEmpty($uri->getQuery());
        $this->assertSame('/v1/message/foo/number/10:action', $uri->getPath());
    }

    public function testMethodWithSimplePlaceholder()
    {
        $message = new MockRequestBody();
        $message->setName('message-name');

        $request = $this->builder->build(
            self::SERVICE_NAME . '/MethodWithSimplePlaceholder',
            $message
        );
        $uri = $request->getUri();

        $this->assertSame('/v1/message-name', $uri->getPath());
    }

    public function testMethodWithAdditionalBindings()
    {
        $message = new MockRequestBody();
        $message->setName('message/foo');
        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithAdditionalBindings', $message);

        $this->assertSame('/v1/message/foo/additional/bindings', $request->getUri()->getPath());

        $message->setName('different/format/foo');
        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithAdditionalBindings', $message);

        $this->assertSame('/v1/different/format/foo/additional/bindings', $request->getUri()->getPath());

        $nestedMessage = new MockRequestBody();
        $nestedMessage->setName('nested/foo');
        $message->setNestedMessage($nestedMessage);
        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithAdditionalBindings', $message);

        $this->assertSame('/v2/nested/foo/additional/bindings', $request->getUri()->getPath());
    }

    public function testMethodWithSpecialJsonMapping()
    {
        $bytesValue = (new BytesValue)
            ->setValue('\000');
        $durationValue = (new Duration)
            ->setSeconds(9001)
            ->setNanos(500000);

        $fieldMask = (new FieldMask)
            ->setPaths(['path1', 'path2']);
        $int64Value = (new Int64Value)
            ->setValue(100);
        $listValue = (new ListValue)
            ->setValues([
                (new Value)->setStringValue('val1'),
                (new Value)->setStringValue('val2')
            ]);
        $stringValue = (new StringValue)
            ->setValue('some-value');
        $structValue = (new Struct)
            ->setFields([
                'test' => (new Value)->setStringValue('val5')
            ]);
        $timestampValue = (new Timestamp)
            ->setSeconds(9001);
        $valueValue = (new Value)
            ->setStringValue('some-value');

        $message = (new MockRequestBody())
            ->setBytesValue($bytesValue)
            ->setDurationValue($durationValue)
            ->setFieldMask($fieldMask)
            ->setInt64Value($int64Value)
            ->setListValue($listValue)
            ->setStringValue($stringValue)
            ->setStructValue($structValue)
            ->setTimestampValue($timestampValue)
            ->setValueValue($valueValue);

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithSpecialJsonMapping', $message);
        $uri = $request->getUri();

        $this->assertStringContainsString('listValue=val1&listValue=val2', (string) $uri);

        $query = Query::parse($uri->getQuery());


        $this->assertSame('XDAwMA==', $query['bytesValue']);
        $this->assertSame('9001.000500s', $query['durationValue']);
        $this->assertSame('path1,path2', $query['fieldMask']);
        $this->assertEquals(100, $query['int64Value']);
        $this->assertEquals(['val1', 'val2'], $query['listValue']);
        $this->assertSame('some-value', $query['stringValue']);
        $this->assertSame('val5', $query['structValue.test']);
        $this->assertSame('1970-01-01T02:30:01Z', $query['timestampValue']);
        $this->assertSame('some-value', $query['valueValue']);
    }

    public function testMethodWithoutPlaceholders()
    {
        $stringValue = (new StringValue)
            ->setValue('some-value');

        $fieldMask = (new FieldMask)
            ->setPaths(['path1', 'path2']);

        $message = (new MockRequestBody())
            ->setStringValue($stringValue)
            ->setFieldMask($fieldMask);

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithoutPlaceholders', $message);
        $query = Query::parse($request->getUri()->getQuery());

        $this->assertSame('path1,path2', $query['fieldMask']);
        $this->assertSame('some-value', $query['stringValue']);
    }

    public function testMethodWithRequiredQueryParametersAndDefaultValues()
    {
        $message = (new MockRequestBody())
            ->setName('')
            ->setNumber(0);

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithRequiredQueryParameters', $message);
        $query = Query::parse($request->getUri()->getQuery());

        $this->assertSame('', $query['name']);
        $this->assertSame('0', $query['number']);
    }


    public function testMethodWithRequiredNestedQueryParameters()
    {
        $nestedMessage = (new MockRequestBody())
            ->setName('some-name')
            ->setNumber(123);
        $message = (new MockRequestBody())
            ->setNestedMessage($nestedMessage);

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithRequiredNestedQueryParameters', $message);
        $query = Query::parse($request->getUri()->getQuery());

        $this->assertSame('some-name', $query['nestedMessage.name']);
        $this->assertSame('123', $query['nestedMessage.number']);
    }


    public function testMethodWithRequiredTimestampQueryParameters()
    {
        $message = (new MockRequestBody())
            ->setTimestampValue(new Timestamp(['seconds' => 1234567]));

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithRequiredTimestampQueryParameters', $message);
        $query = Query::parse($request->getUri()->getQuery());

        $dateTime = (new \DateTime)->setTimestamp(1234567);
        $this->assertSame($dateTime->format('Y-m-d\TH:i:s\Z'), $query['timestampValue']);
    }

    public function testMethodWithRequiredDoubleNestedQueryParameter()
    {
        $doubleNestedMessage = (new MockRequestBody())
            ->setName('double-nested-name');
        $nestedMessage = (new MockRequestBody())
            ->setName('some-name')
            ->setNestedMessage($doubleNestedMessage);
        $message = (new MockRequestBody())
            ->setNestedMessage($nestedMessage);

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithRequiredNestedQueryParameters', $message);
        $query = Query::parse($request->getUri()->getQuery());

        $this->assertSame('some-name', $query['nestedMessage.name']);
        $this->assertSame('double-nested-name', $query['nestedMessage.nestedMessage']);
    }

    public function testMethodWithRequiredDoubleNestedQueryParameterArray()
    {
        // Adding another property decodes it as array
        $doubleNestedMessage = (new MockRequestBody())
            ->setName('double-nested-name')
            ->setNumber(123);
        $nestedMessage = (new MockRequestBody())
            ->setName('some-name')
            ->setNestedMessage($doubleNestedMessage);
        $message = (new MockRequestBody())
            ->setNestedMessage($nestedMessage);

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithRequiredNestedQueryParameters', $message);
        $query = Query::parse($request->getUri()->getQuery());

        $this->assertSame(['double-nested-name', '123'], $query['nestedMessage.nestedMessage']);
    }

    public function testMethodWithComplexMessageInQueryString()
    {
        $message = (new MockRequestBody())
            ->setNestedMessage(
                (new MockRequestBody)
                    ->setName('some-name')
                    ->setNumber(10)
            );

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithoutPlaceholders', $message);
        $query = Query::parse($request->getUri()->getQuery());

        $this->assertSame('some-name', $query['nestedMessage.name']);
        $this->assertEquals(10, $query['nestedMessage.number']);
    }

    public function testMethodWithOneOfInQueryString()
    {
        $message = (new MockRequestBody())
            ->setField1('some-value');

        $request = $this->builder->build(self::SERVICE_NAME . '/MethodWithoutPlaceholders', $message);
        $query = Query::parse($request->getUri()->getQuery());

        $this->assertSame('some-value', $query['field1']);
    }

    public function testMethodWithNumericEnumsQueryParam()
    {
        $request = $this->numericEnumsBuilder->build(self::SERVICE_NAME . '/MethodWithNumericEnumsQueryParam', new MockRequestBody());
        $query = Query::parse($request->getUri()->getQuery());

        $this->assertEquals('json;enum-encoding=int', $query['$alt']);
    }

    public function testThrowsExceptionWithNonMatchingFormat()
    {
        $message = new MockRequestBody();
        $message->setName('invalid/name/format');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Could not map bindings for test.interface.v1.api/MethodWithAdditionalBindings to any Uri template.');

        $this->builder->build(self::SERVICE_NAME . '/MethodWithAdditionalBindings', $message);
    }

    public function testThrowsExceptionWithNonExistantMethod()
    {
        $message = new MockRequestBody();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to build request, as the provided path (myResource/doesntExist) was not found in the configuration.');

        $this->builder->build('myResource/doesntExist', $message);
    }
}
