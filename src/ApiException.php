<?php
/*
 * Copyright 2016 Google LLC
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
use Google\Protobuf\Internal\RepeatedField;
use Google\Rpc\Status;
use GuzzleHttp\Exception\RequestException;

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

    // Returns null if metadata does not contain error info, or return containsErrorInfo() array
    // if the metadata does contain error info.
    public static function decodeMetadataErrorInfo($metadata)
    {
        $details = [];
        // For REST-based responses, the $metadata does not need to be decoded.
        $details = self::containsErrorInfo($metadata);
        // ApiExceptions created from RPC status have metadata that is an array of objects.
        if (is_object(reset($metadata))) {
            $metadataRpcStatus = Serializer::decodeAnyMessages($metadata);
            $details = self::containsErrorInfo($metadataRpcStatus);
        } else {
            // For GRPC-based responses, the $metadata needs to be decoded.
            $metadataGrpc = Serializer::decodeMetadata($metadata);
            if (reset($metadataGrpc)) {
                $details = self::containsErrorInfo($metadataGrpc);
            }
        }
        return $details['containsErrorInfo'] ? $details : null;
    }
    
    public function getReason()
    {
        $metadata = $this->metadata;
        $decodedMetadata = self::decodeMetadataErrorInfo($metadata);
        return isset($decodedMetadata['reason']) ? $decodedMetadata['reason'] : null;
    }

    public function getDomain()
    {
        $metadata = $this->metadata;
        $decodedMetadata = self::decodeMetadataErrorInfo($metadata);
        return isset($decodedMetadata['domain']) ? $decodedMetadata['domain'] : null;
    }

    public function getErrorInfoMetadata()
    {
        $metadata = $this->metadata;
        $decodedMetadata = self::decodeMetadataErrorInfo($metadata);
        return isset($decodedMetadata['errorInfoMetadata']) ? $decodedMetadata['errorInfoMetadata'] : null;
    }

    /**
     * @param \stdClass $status
     * @return ApiException
     */
    public static function createFromStdClass($status)
    {
        $metadata = property_exists($status, 'metadata') ? $status->metadata : null;
        return self::create(
            $status->details,
            $status->code,
            $metadata,
            Serializer::decodeMetadata($metadata)
        );
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
        return self::create(
            $basicMessage,
            $rpcCode,
            $metadata,
            Serializer::decodeMetadata($metadata),
            $previous
        );
    }

    /**
     * For REST-based responses, the metadata does not need to be decoded.
     *
     * @param string $basicMessage
     * @param int $rpcCode
     * @param array|null $metadata
     * @param \Exception $previous
     * @return ApiException
     */
    public static function createFromRestApiResponse(
        $basicMessage,
        $rpcCode,
        array $metadata = null,
        \Exception $previous = null
    ) {
        return self::create(
            $basicMessage,
            $rpcCode,
            $metadata,
            is_null($metadata) ? [] : $metadata,
            $previous
        );
    }
    
    /**
     * Checks if decoded metadata includes errorInfo message.
     * @param array $decodedMetadata
     * @return array $errorInfo {
     *     @type boolean $containsErrorInfo
     *     @type string $reason
     *     @type string $domain
     *     @type array $errorInfoMetadata
     * }
     */
    public static function containsErrorInfo(array $decodedMetadata)
    {
        $errorInfo = [];
        $errorInfo['containsErrorInfo'] = false;
        if (empty($decodedMetadata)) {
            return $errorInfo;
        }
        foreach ($decodedMetadata as $value) {
            $isErrorInfoArray = array_key_exists('reason', $value) && array_key_exists('domain', $value);
            if ($isErrorInfoArray) {
                $errorInfo['containsErrorInfo'] = true;
                $errorInfo['reason'] = $value['reason'];
                $errorInfo['domain'] = $value['domain'];
                $errorInfo['errorInfoMetadata'] = isset($value['metadata']) ? $value['metadata'] : null;
                return $errorInfo;
            }
        }
        return $errorInfo;
    }

    /**
     * Construct an ApiException with a useful message, including decoded metadata.
     * If the decoded metadata includes an errorInfo message, then the domain, reason,
     * and metadata fields from that message are hoisted directly into the error.
     *
     * @param string $basicMessage
     * @param int $rpcCode
     * @param mixed[]|RepeatedField $metadata
     * @param array $decodedMetadata
     * @param \Exception|null $previous
     * @return ApiException
     */
    private static function create($basicMessage, $rpcCode, $metadata, array $decodedMetadata, $previous = null)
    {
        $containsErrorInfo = self::containsErrorInfo($decodedMetadata);
        $rpcStatus = ApiStatus::statusFromRpcCode($rpcCode);
        $messageData = [
            'message' => $basicMessage,
            'code' => $rpcCode,
            'status' => $rpcStatus,
            'details' => $decodedMetadata
        ];
        if ($containsErrorInfo['containsErrorInfo']) {
            $messageData = array_merge([
                'domain' => $containsErrorInfo['domain'],
                'reason' => $containsErrorInfo['reason'],
                'errorInfoMetadata' => $containsErrorInfo['errorInfoMetadata'],
            ], $messageData);
        }

        $message = json_encode($messageData, JSON_PRETTY_PRINT);

        if ($metadata instanceof RepeatedField) {
            $metadata = iterator_to_array($metadata);
        }

        return new ApiException($message, $rpcCode, $rpcStatus, [
            'previous' => $previous,
            'metadata' => $metadata,
            'basicMessage' => $basicMessage,
        ]);
    }
    
    /**
     * @param Status $status
     * @return ApiException
     */
    public static function createFromRpcStatus(Status $status)
    {
        return self::create(
            $status->getMessage(),
            $status->getCode(),
            $status->getDetails(),
            Serializer::decodeAnyMessages($status->getDetails())
        );
    }

    /**
     * Creates an ApiException from a GuzzleHttp RequestException.
     *
     * @param RequestException $ex
     * @param boolean $isStream
     * @return ApiException
     * @throws ValidationException
     */
    public static function createFromRequestException(RequestException $ex, $isStream = false)
    {
        $res = $ex->getResponse();
        $body = (string) $res->getBody();
        $decoded = json_decode($body, true);
        
        // A streaming response body will return one error in an array. Parse
        // that first (and only) error message, if provided.
        if ($isStream && isset($decoded[0])) {
            $decoded = $decoded[0];
        }

        if ($error = $decoded['error']) {
            $basicMessage = $error['message'];
            $code = isset($error['status'])
                ? ApiStatus::rpcCodeFromStatus($error['status'])
                : $ex->getCode();
            $metadata = isset($error['details']) ? $error['details'] : null;
            return static::createFromRestApiResponse($basicMessage, $code, $metadata);
        }
        // Use the RPC code instead of the HTTP Status Code.
        $code = ApiStatus::rpcCodeFromHttpStatusCode($res->getStatusCode());
        return static::createFromApiResponse($body, $code);
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
