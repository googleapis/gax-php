<?php
/*
 * Copyright 2016, Google Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following disclaimer
 * in the documentation and/or other materials provided with the
 * distribution.
 *     * Neither the name of Google Inc. nor the names of its
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
namespace Google\ApiCore\Tests\Unit;

use Google\ApiCore\PathTemplate\ResourceTemplate;
use PHPUnit\Framework\TestCase;

class ResourceTemplateTest extends TestCase
{
    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage Unexpected
     */
    public function testFailInvalidToken()
    {
        new ResourceTemplate('hello/wor*ld');
    }

    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage Could not match
     */
    public function testFailWhenImpossibleMatch01()
    {
        $template = new ResourceTemplate('hello/world');
        $template->match('hello');
    }

    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage Could not match
     */
    public function testFailWhenImpossibleMatch02()
    {
        $template = new ResourceTemplate('hello/world');
        $template->match('hello/world/fail');
    }

    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage Could not match
     */
    public function testFailMismatchedLiteral()
    {
        $template = new ResourceTemplate('hello/world');
        $template->match('hello/world2');
    }

    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage cannot contain more than one path wildcard
     */
    public function testFailWhenMultiplePathWildcards()
    {
        new ResourceTemplate('buckets/*/**/**/objects/*');
    }

    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage Unexpected
     */
    public function testFailIfInnerBinding()
    {
        new ResourceTemplate('buckets/{hello={world}}');
    }

    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage Unexpected
     */
    public function testFailUnexpectedEof()
    {
        new ResourceTemplate('a/{hello=world');
    }

    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage Cannot construct ResourceTemplate from empty string
     */
    public function testFailNullString()
    {
        new ResourceTemplate(null);
    }

    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage Cannot construct ResourceTemplate from empty string
     */
    public function testFailEmptyString()
    {
        new ResourceTemplate("");
    }

    public function testMatchAtomicResourceName()
    {
        $template = new ResourceTemplate('buckets/*/*/objects/*');
        $this->assertEquals(
            ['$0' => 'f', '$1' => 'o', '$2' => 'bar'],
            $template->match('buckets/f/o/objects/bar')
        );
        $template = new ResourceTemplate('buckets/{hello}');
        $this->assertEquals(
            ['hello' => 'world'],
            $template->match('buckets/world')
        );
        $template = new ResourceTemplate('buckets/{hello=*}');
        $this->assertEquals(
            ['hello' => 'world'],
            $template->match('buckets/world')
        );
    }

    public function testMatchEscapedChars()
    {
        $template = new ResourceTemplate('buckets/*/objects');
        $this->assertEquals(
            ['$0' => 'hello%2F%2Bworld'],
            $template->match('buckets/hello%2F%2Bworld/objects')
        );
    }

    public function testMatchWildcardWithColonInMiddle()
    {
        $template = new ResourceTemplate('buckets/*:action/objects');
        $this->assertEquals(
            ['$0' => 'foo'],
            $template->match('buckets/foo:action/objects')
        );
    }

    public function testMatchColonInWildcard()
    {
        $template = new ResourceTemplate('buckets/*/*/*/objects/*');
        $url = $template->render(
            ['$0' => 'f', '$1' => 'o', '$2' => 'o', '$3' => 'google.com:a-b']
        );
        $this->assertEquals($url, 'buckets/f/o/o/objects/google.com:a-b');
    }

    public function testMatchUnboundedWildcardWithColonInMiddle()
    {
        $template = new ResourceTemplate('buckets/*/objects/**:action/path');
        $this->assertEquals(
            ['$0' => 'foo', '$1' => 'bar/baz'],
            $template->match('buckets/foo/objects/bar/baz:action/path')
        );
    }

    public function testMatchTemplateWithUnboundedWildcard()
    {
        $template = new ResourceTemplate('buckets/*/objects/**');
        $this->assertEquals(
            ['$0' => 'foo', '$1' => 'bar/baz'],
            $template->match('buckets/foo/objects/bar/baz')
        );
    }

    public function testMatchWithUnboundInMiddle()
    {
        $template = new ResourceTemplate('bar/**/foo/*');
        $this->assertEquals(
            ['$0' => 'foo/foo', '$1' => 'bar'],
            $template->match('bar/foo/foo/foo/bar')
        );
    }

    public function testRenderAtomicResource()
    {
        $template = new ResourceTemplate('buckets/*/*/*/objects/*');
        $url = $template->render(
            ['$0' => 'f', '$1' => 'o', '$2' => 'o', '$3' => 'google.com:a-b']
        );
        $this->assertEquals($url, 'buckets/f/o/o/objects/google.com:a-b');
    }

    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage Rendering error
     */
    public function testRenderFailWhenTooFewVariables()
    {
        $template = new ResourceTemplate('buckets/*/*/*/objects/*');
        $template->render(['$0' => 'f', '$1' => 'l', '$2' => 'o']);
    }

    public function testRenderWithUnboundInMiddle()
    {
        $template = new ResourceTemplate('bar/**/foo/*');
        $url = $template->render(['$0' => '1/2', '$1' => '3']);
        $this->assertEquals($url, 'bar/1/2/foo/3');
    }

    public function testToString()
    {
        $template = new ResourceTemplate('bar/**/foo/*');
        $this->assertEquals((string) $template, 'bar/**/foo/*');
        $template = new ResourceTemplate('buckets/*/objects/*');
        $this->assertEquals(
            (string) ($template),
            'buckets/*/objects/*'
        );
        $template = new ResourceTemplate('buckets/{hello}');
        $this->assertEquals((string) ($template), 'buckets/{hello=*}');
        $template = new ResourceTemplate('buckets/{hello=what}/{world}');
        $this->assertEquals(
            (string) ($template),
            'buckets/{hello=what}/{world=*}'
        );
        $template = new ResourceTemplate('buckets/helloazAZ09-.~_what');
        $this->assertEquals(
            (string) ($template),
            'buckets/helloazAZ09-.~_what'
        );
    }

    public function testSubstitutionOddChars()
    {
        $template = new ResourceTemplate('projects/{project}/topics/{topic}');
        $url = $template->render(
            ['project' => 'google.com:proj-test', 'topic' => 'some-topic']
        );
        $this->assertEquals(
            $url,
            'projects/google.com:proj-test/topics/some-topic'
        );
        $template = new ResourceTemplate('projects/{project}/topics/{topic}');
        $url = $template->render(
            ['project' => 'g.,;:~`!@#$%^&()+-', 'topic' => 'sdf<>,.?[]']
        );
        $this->assertEquals(
            $url,
            'projects/g.,;:~`!@#$%^&()+-/topics/sdf<>,.?[]'
        );
    }
}
