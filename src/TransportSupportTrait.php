<?php
/*
 * Copyright 2024 Google LLC
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

use Google\ApiCore\Transport\TransportInterface;
use Google\ApiCore\Options\TransportOptions;
use Google\ApiCore\Transport\GrpcFallbackTransport;
use Google\ApiCore\Transport\GrpcTransport;
use Google\ApiCore\Transport\RestTransport;

/**
 * Common functions used to work with various clients.
 *
 * @internal
 */
trait TransportSupportTrait
{
    private ?TransportInterface $transport = null;

    /**
     * Initiates an orderly shutdown in which preexisting calls continue but new
     * calls are immediately cancelled.
     *
     * @experimental
     */
    public function close()
    {
        $this->transport->close();
    }

    /**
     * Get the transport for the client. This method is protected to support
     * use by customized clients.
     *
     * @access private
     * @return TransportInterface
     */
    protected function getTransport()
    {
        return $this->transport;
    }

    /**
     * @param string $apiEndpoint
     * @param string $transport
     * @param TransportOptions|array $transportConfig
     * @param callable $clientCertSource
     * @return TransportInterface
     * @throws ValidationException
     */
    private function createTransport(
        string $apiEndpoint,
        $transport,
        $transportConfig,
        callable $clientCertSource = null
    ) {
        if (!is_string($transport)) {
            throw new ValidationException(
                "'transport' must be a string, instead got:" .
                print_r($transport, true)
            );
        }
        $supportedTransports = self::supportedTransports();
        if (!in_array($transport, $supportedTransports)) {
            throw new ValidationException(sprintf(
                'Unexpected transport option "%s". Supported transports: %s',
                $transport,
                implode(', ', $supportedTransports)
            ));
        }
        $configForSpecifiedTransport = $transportConfig[$transport] ?? [];
        if (is_array($configForSpecifiedTransport)) {
            $configForSpecifiedTransport['clientCertSource'] = $clientCertSource;
        } else {
            $configForSpecifiedTransport->setClientCertSource($clientCertSource);
            $configForSpecifiedTransport = $configForSpecifiedTransport->toArray();
        }
        switch ($transport) {
            case 'grpc':
                // Setting the user agent for gRPC requires special handling
                if (isset($this->agentHeader['User-Agent'])) {
                    if ($configForSpecifiedTransport['stubOpts']['grpc.primary_user_agent'] ??= '') {
                        $configForSpecifiedTransport['stubOpts']['grpc.primary_user_agent'] .= ' ';
                    }
                    $configForSpecifiedTransport['stubOpts']['grpc.primary_user_agent'] .=
                        $this->agentHeader['User-Agent'][0];
                }
                return GrpcTransport::build($apiEndpoint, $configForSpecifiedTransport);
            case 'grpc-fallback':
                return GrpcFallbackTransport::build($apiEndpoint, $configForSpecifiedTransport);
            case 'rest':
                if (!isset($configForSpecifiedTransport['restClientConfigPath'])) {
                    throw new ValidationException(
                        "The 'restClientConfigPath' config is required for 'rest' transport."
                    );
                }
                $restConfigPath = $configForSpecifiedTransport['restClientConfigPath'];
                return RestTransport::build($apiEndpoint, $restConfigPath, $configForSpecifiedTransport);
            default:
                throw new ValidationException(
                    "Unexpected 'transport' option: $transport. " .
                    "Supported values: ['grpc', 'rest', 'grpc-fallback']"
                );
        }
    }

    /**
     * @return string
     */
    private static function defaultTransport()
    {
        return self::getGrpcDependencyStatus()
            ? 'grpc'
            : 'rest';
    }
}
