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

use Google\ApiCore\PathTemplate\AbsoluteResourceTemplate;
use PHPUnit\Framework\TestCase;

class AbsoluteResourceTemplateTest extends TestCase
{
    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage Cannot construct AbsoluteResourceTemplate from empty string
     */
    public function testFailNullString()
    {
        new AbsoluteResourceTemplate(null);
    }

    /**
     * @expectedException \Google\ApiCore\ValidationException
     * @expectedExceptionMessage Cannot construct AbsoluteResourceTemplate from empty string
     */
    public function testFailEmptyString()
    {
        new AbsoluteResourceTemplate("");
    }

    public function testMatchAtomicResourceName()
    {
        $template = new AbsoluteResourceTemplate('/buckets/*/*/objects/*');
        $this->assertEquals(
            ['$0' => 'f', '$1' => 'o', '$2' => 'bar'],
            $template->match('/buckets/f/o/objects/bar')
        );
        $template = new AbsoluteResourceTemplate('/buckets/{hello}');
        $this->assertEquals(
            ['hello' => 'world'],
            $template->match('/buckets/world')
        );
        $template = new AbsoluteResourceTemplate('/buckets/{hello=*}');
        $this->assertEquals(
            ['hello' => 'world'],
            $template->match('/buckets/world')
        );
    }

    public function testMatchWildcardWithColonInMiddle()
    {
        $template = new AbsoluteResourceTemplate('/buckets/*:action/objects');
        $this->assertEquals(
            ['$0' => 'foo'],
            $template->match('/buckets/foo:action/objects')
        );
    }

    public function testMatchWildcardWithColon()
    {
        $template = new AbsoluteResourceTemplate('/buckets/*:action');
        $this->assertEquals(
            ['$0' => 'foo'],
            $template->match('/buckets/foo:action')
        );
    }

    public function testMatchColonInWildcardAndTemplate()
    {
        $template = new AbsoluteResourceTemplate('/buckets/*/*/*/objects/*:action');
        $url = $template->render(
            ['$0' => 'f', '$1' => 'o', '$2' => 'o', '$3' => 'google.com:a-b']
        );
        $this->assertEquals($url, '/buckets/f/o/o/objects/google.com:a-b:action');
    }

    public function testMatchUnboundedWildcardWithColon()
    {
        $template = new AbsoluteResourceTemplate('/buckets/*/objects/**:action');
        $this->assertEquals(
            ['$0' => 'foo', '$1' => 'bar/baz'],
            $template->match('/buckets/foo/objects/bar/baz:action')
        );
    }

    public function testMatchUnboundedWildcardWithColonInMiddle()
    {
        $template = new AbsoluteResourceTemplate('/buckets/*/objects/**:action/path');
        $this->assertEquals(
            ['$0' => 'foo', '$1' => 'bar/baz'],
            $template->match('/buckets/foo/objects/bar/baz:action/path')
        );
    }

    public function testToString()
    {
        $template = new AbsoluteResourceTemplate('/bar/**/foo/*');
        $this->assertEquals((string) $template, '/bar/**/foo/*');
        $template = new AbsoluteResourceTemplate('/buckets/*/objects/*');
        $this->assertEquals(
            (string) ($template),
            '/buckets/*/objects/*'
        );
        $template = new AbsoluteResourceTemplate('/buckets/{hello}');
        $this->assertEquals((string) ($template), '/buckets/{hello=*}');
        $template = new AbsoluteResourceTemplate('/buckets/{hello=what}/{world}');
        $this->assertEquals(
            (string) ($template),
            '/buckets/{hello=what}/{world=*}'
        );
        $template = new AbsoluteResourceTemplate('/buckets/helloazAZ09-.~_what');
        $this->assertEquals(
            (string) ($template),
            '/buckets/helloazAZ09-.~_what'
        );
    }
}
