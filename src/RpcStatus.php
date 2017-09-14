<?php
/*
 * Copyright 2017, Google Inc.
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
namespace Google\GAX;

use Google\Rpc\Code;

class RpcStatus
{
    const OK = 'OK';
    const CANCELLED = 'CANCELLED';
    const UNKNOWN = 'UNKNOWN';
    const INVALID_ARGUMENT = 'INVALID_ARGUMENT';
    const DEADLINE_EXCEEDED = 'DEADLINE_EXCEEDED';
    const NOT_FOUND = 'NOT_FOUND';
    const ALREADY_EXISTS = 'ALREADY_EXISTS';
    const PERMISSION_DENIED = 'PERMISSION_DENIED';
    const RESOURCE_EXHAUSTED = 'RESOURCE_EXHAUSTED';
    const FAILED_PRECONDITION = 'FAILED_PRECONDITION';
    const ABORTED = 'ABORTED';
    const OUT_OF_RANGE = 'OUT_OF_RANGE';
    const UNIMPLEMENTED = 'UNIMPLEMENTED';
    const INTERNAL = 'INTERNAL';
    const UNAVAILABLE = 'UNAVAILABLE';
    const DATA_LOSS = 'DATA_LOSS';
    const UNAUTHENTICATED = 'UNAUTHENTICATED';

    const UNRECOGNIZED_STATUS = 'UNRECOGNIZED_STATUS';

    private static $knownStatusMap = [
        RpcStatus::OK => Code::OK,
        RpcStatus::CANCELLED => Code::CANCELLED,
        RpcStatus::UNKNOWN => Code::UNKNOWN,
        RpcStatus::INVALID_ARGUMENT => Code::INVALID_ARGUMENT,
        RpcStatus::DEADLINE_EXCEEDED => Code::DEADLINE_EXCEEDED,
        RpcStatus::NOT_FOUND => Code::NOT_FOUND,
        RpcStatus::ALREADY_EXISTS => Code::ALREADY_EXISTS,
        RpcStatus::PERMISSION_DENIED => Code::PERMISSION_DENIED,
        RpcStatus::RESOURCE_EXHAUSTED => Code::RESOURCE_EXHAUSTED,
        RpcStatus::FAILED_PRECONDITION => Code::FAILED_PRECONDITION,
        RpcStatus::ABORTED => Code::ABORTED,
        RpcStatus::OUT_OF_RANGE => Code::OUT_OF_RANGE,
        RpcStatus::UNIMPLEMENTED => Code::UNIMPLEMENTED,
        RpcStatus::INTERNAL => Code::INTERNAL,
        RpcStatus::UNAVAILABLE => Code::UNAVAILABLE,
        RpcStatus::DATA_LOSS => Code::DATA_LOSS,
        RpcStatus::UNAUTHENTICATED => Code::UNAUTHENTICATED,
    ];
    private static $rpcCodeToStatusMap = [
        Code::OK => RpcStatus::OK,
        Code::CANCELLED => RpcStatus::CANCELLED,
        Code::UNKNOWN => RpcStatus::UNKNOWN,
        Code::INVALID_ARGUMENT => RpcStatus::INVALID_ARGUMENT,
        Code::DEADLINE_EXCEEDED => RpcStatus::DEADLINE_EXCEEDED,
        Code::NOT_FOUND => RpcStatus::NOT_FOUND,
        Code::ALREADY_EXISTS => RpcStatus::ALREADY_EXISTS,
        Code::PERMISSION_DENIED => RpcStatus::PERMISSION_DENIED,
        Code::RESOURCE_EXHAUSTED => RpcStatus::RESOURCE_EXHAUSTED,
        Code::FAILED_PRECONDITION => RpcStatus::FAILED_PRECONDITION,
        Code::ABORTED => RpcStatus::ABORTED,
        Code::OUT_OF_RANGE => RpcStatus::OUT_OF_RANGE,
        Code::UNIMPLEMENTED => RpcStatus::UNIMPLEMENTED,
        Code::INTERNAL => RpcStatus::INTERNAL,
        Code::UNAVAILABLE => RpcStatus::UNAVAILABLE,
        Code::DATA_LOSS => RpcStatus::DATA_LOSS,
        Code::UNAUTHENTICATED => RpcStatus::UNAUTHENTICATED,
    ];

    /**
     * @param string $status
     * @return bool
     */
    public static function validateStatus($status)
    {
        return array_key_exists($status, self::$knownStatusMap);
    }

    /**
     * @param int $code
     * @return string
     */
    public static function statusFromRpcCode($code)
    {
        if (array_key_exists($code, self::$rpcCodeToStatusMap)) {
            return self::$rpcCodeToStatusMap[$code];
        }
        return RpcStatus::UNRECOGNIZED_STATUS;
    }

    private function __construct()
    {
    }
}
