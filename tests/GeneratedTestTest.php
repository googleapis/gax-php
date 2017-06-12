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
namespace Google\GAX\UnitTests;

use Google\Api\Monitoring_MonitoringDestination;
use Google\GAX\Testing\GeneratedTest;
use PHPUnit_Framework_ExpectationFailedException;

class GeneratedTestTest extends GeneratedTest
{
    /**
     * @dataProvider successCases
     */
    public function testSuccess($expected, $actual)
    {
        $this->assertRepeatedFieldEquals($expected, $actual);
    }

    /**
     * @dataProvider failureCases
     */
    public function testFailure($expected, $actual)
    {
        try {
            $this->assertRepeatedFieldEquals($expected, $actual);
        } catch (PHPUnit_Framework_ExpectationFailedException $ex) {
            // As expected the assertion failed, silently return
            return;
        }
        // The assertion did not fail, make the test fail
        $this->fail('This test did not fail as expected');
    }

    public function successCases()
    {
        $monitoringA = new Monitoring_MonitoringDestination();
        $monitoringA->setMonitoredResource("type");
        $monitoringB = new Monitoring_MonitoringDestination();
        $monitoringB->setMonitoredResource("type");

        $emptyRepeatedA = $monitoringA->getMetrics();
        $emptyRepeatedB = $monitoringB->getMetrics();

        $repeatedA = clone $emptyRepeatedA;
        $repeatedA[] = "metric";

        $repeatedB = clone $emptyRepeatedB;
        $repeatedB[] = "metric";

        return [
            [[], []],
            [[], $emptyRepeatedB],
            [$emptyRepeatedA, []],
            [$emptyRepeatedA, $emptyRepeatedB],
            [[1, 2], [1, 2]],
            [["abc", $monitoringA], ["abc", $monitoringB]],
            [["metric"], $repeatedB],
            [$repeatedA, ["metric"]],
            [$repeatedA, $repeatedB],
        ];
    }

    public function failureCases()
    {
        $monitoringA = new Monitoring_MonitoringDestination();
        $monitoringA->setMonitoredResource("typeA");
        $monitoringB = new Monitoring_MonitoringDestination();
        $monitoringB->setMonitoredResource("typeB");

        $emptyRepeatedA = $monitoringA->getMetrics();
        $emptyRepeatedB = $monitoringB->getMetrics();

        $repeatedA = clone $emptyRepeatedA;
        $repeatedA[] = "metricA";

        $repeatedB = clone $emptyRepeatedB;
        $repeatedB[] = "metricB";

        return [
            [[], [1]],
            [[1], []],
            [[1], [2]],
            [[$monitoringA], [$monitoringB]],
            [[], $repeatedB],
            [$repeatedA, []],
            [$emptyRepeatedA, [1]],
            [[1], $emptyRepeatedB],
            [$emptyRepeatedA, $repeatedB],
            [$repeatedA, $emptyRepeatedB],
            [$repeatedA, $repeatedB],
        ];
    }
}
