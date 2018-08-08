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
namespace Google\ApiCore;

use Exception;
use Google\Protobuf\Any;
use Google\Rpc\Status;

/**
 * Represents an exception thrown during an RPC.
 */
class ApiException extends Exception
{
    private $status;
    private $metadata;
    private $basicMessage;

    /**
     * ApiException constructor.
     * @param string $message
     * @param int $code
     * @param string $status
     * @param array $optionalArgs {
     *     @type Exception|null $previous
     *     @type array|null $metadata
     *     @type string|null $basicMessage
     * }
     */
    public function __construct(
        $message,
        $code,
        $status,
        array $optionalArgs = []
    ) {
        $optionalArgs += [
            'previous' => null,
            'metadata' => null,
            'basicMessage' => $message,
        ];
        parent::__construct($message, $code, $optionalArgs['previous']);
        $this->status = $status;
        $this->metadata = $optionalArgs['metadata'];
        $this->basicMessage = $optionalArgs['basicMessage'];
    }

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param \stdClass $status
     * @return ApiException
     */
    public static function createFromStdClass($status)
    {
        $basicMessage = $status->details;
        $code = $status->code;
        $metadata = property_exists($status, 'metadata') ? $status->metadata : null;

        return self::createFromApiResponse($basicMessage, $code, $metadata);
    }

    /**
     * @param string $basicMessage
     * @param int $rpcCode
     * @param array|null $metadata
     * @param \Exception $previous
     * @return ApiException
     */
    public static function createFromApiResponse(
        $basicMessage,
        $rpcCode,
        array $metadata = null,
        \Exception $previous = null
    ) {
        $message = self::createMessage($basicMessage, $rpcCode, Serializer::decodeMetadata($metadata));
        $rpcStatus = ApiStatus::statusFromRpcCode($rpcCode);

        return new ApiException($message, $rpcCode, $rpcStatus, [
            'previous' => $previous,
            'metadata' => $metadata,
            'basicMessage' => $basicMessage,

        ]);
    }

    private static function create(
        $basicMessage,
        $rpcCode,
        array $metadata = null,
        \Exception $previous = null
    ) {
        $rpcStatus = ApiStatus::statusFromRpcCode($rpcCode);

        $messageData = [
            'message' => $basicMessage,
            'code' => $rpcCode,
            'status' => $rpcStatus,
            'details' => $metadata,
        ];

        $message = json_encode($messageData, JSON_PRETTY_PRINT);

        return new ApiException($message, $rpcCode, $rpcStatus, [
            'metadata' => $metadata,
            'basicMessage' => $basicMessage,
            'previous' => $previous,
        ]);
    }

    /**
     * @param Status $status
     * @return ApiException
     */
    public static function createFromRpcStatus(Status $status)
    {
        $basicMessage = $status->getMessage();
        $rpcCode = $status->getCode();
        $metadata = $status->getDetails();

        $decodedMetadata = [];
        foreach ($status->getDetails() as $any) {
            /** @var Any $any */
            try {
                $unpacked = $any->unpack();
                $decodedMetadata[] = Serializer::serializeToPhpArray($unpacked);
            } catch (\Exception $ex) {
                // failed to unpack the $any object - use the Any object directly
                $decodedMetadata[] = Serializer::serializeToPhpArray($any);
            }
        }

        $message = self::createMessage($status->getMessage(), $status->getCode(), $decodedMetadata);
        $rpcStatus = ApiStatus::statusFromRpcCode($rpcCode);

        return new ApiException($message, $rpcCode, $rpcStatus, [
            'metadata' => $metadata,
            'basicMessage' => $basicMessage,
        ]);
    }

    /**
     * Construct a message string that contains useful debugging information.
     *
     * @param string $basicMessage
     * @param int $rpcCode
     * @param array $details
     * @return string
     */
    private static function createMessage($basicMessage, $rpcCode, array $details)
    {
        $messageData = [
            'message' => $basicMessage,
            'code' => $rpcCode,
            'status' => ApiStatus::statusFromRpcCode($rpcCode),
            'details' => $details
        ];

        $message = json_encode($messageData, JSON_PRETTY_PRINT);

        return $message;
    }

    /**
     * @return null|string
     */
    public function getBasicMessage()
    {
        return $this->basicMessage;
    }

    /**
     * @return mixed[]
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * String representation of ApiException
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": $this->message\n";
    }
}
