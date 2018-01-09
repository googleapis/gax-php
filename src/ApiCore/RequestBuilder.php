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

namespace Google\ApiCore;

use Google\Protobuf\Internal\Message;
use Google\Protobuf\Internal\RepeatedField;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Builds a PSR-7 request from a set of request information.
 */
class RequestBuilder
{
    use UriTrait;

    private $baseUri;
    private $restConfig;

    /**
     * @param string $baseUri
     * @param string $restConfigPath
     */
    public function __construct($baseUri, $restConfigPath)
    {
        $this->baseUri = $baseUri;
        $this->restConfig = require($restConfigPath);
    }

    /**
     * @param string $path
     * @param Message $message
     * @param array $headers
     * @return RequestInterface
     * @throws \RuntimeException
     */
    public function build($path, Message $message, array $headers = [])
    {
        list($interface, $method) = explode('/', $path);

        if (isset($this->restConfig['interfaces'][$interface][$method])) {
            $config = $this->restConfig['interfaces'][$interface][$method] + [
                'placeholders' => [],
                'body' => null
            ];
            $uri = $this->buildUri(
                $config['uriTemplate'],
                $config['placeholders'],
                $message
            );
            $body = null;

            if ($config['body'] === '*') {
                $body = $message->serializeToJsonString();
            } else {
                $queryParams = [];

                foreach ($this->getAllProperties($message) as $name => $value) {
                    if (array_key_exists($name, $config['placeholders'])) {
                        continue;
                    }

                    if ($name === $config['body']) {
                        $body = $this->getPrivatePropertyValue($message, $name)
                            ->serializeToJsonString();
                        continue;
                    }

                    $value = $this->getPrivatePropertyValue($message, $name);
                    if ($value instanceof RepeatedField) {
                        $value = iterator_to_array($value);
                    }

                    $queryParams[$name] = $value;
                }

                if ($queryParams) {
                    $uri = $this->buildUriWithQuery(
                        $uri,
                        $queryParams
                    );
                }
            }

            return new Request(
                $config['method'],
                $uri,
                ['Content-Type' => 'application/json'] + $headers,
                $body
            );
        }

        throw new \RuntimeException(
            "Failed to build request, as the provided path ($path) was not found in the configuration."
        );
    }

    /**
     * @param string $uriTemplate
     * @param array $placeholders
     * @param Message $message
     * @return UriInterface
     */
    private function buildUri($uriTemplate, array $placeholders, Message $message)
    {
        $template = new PathTemplate($uriTemplate);
        $bindings = [];

        foreach ($placeholders as $placeholder => $metadata) {
            $value = array_reduce(
                $metadata['getters'],
                function (Message $result, $getter) {
                    return $result->$getter();
                },
                $message
            );

            if (isset($metadata['format'])) {
                $formatTemplate = new PathTemplate($metadata['format']);
                $value = $formatTemplate->render([$placeholder => $value]);
            }

            $bindings[$placeholder] = $value;
        }

        return Psr7\uri_for(
            sprintf(
                'https://%s/%s',
                $this->baseUri,
                $template->render($bindings)
            )
        );
    }

    private function getPrivatePropertyValue(Message $message, $propertyName)
    {
        $privatePropertyValueFunc = \Closure::bind(function (Message $message, $propertyName) {
            return $message->$propertyName;
        }, null, $message);

        return $privatePropertyValueFunc($message, $propertyName);
    }

    private function getAllProperties(Message $message)
    {
        $privatePropertiesFunc = \Closure::bind(function (Message $message) {
            return get_class_vars(get_class($message));
        }, null, $message);

        return $privatePropertiesFunc($message);
    }
}
