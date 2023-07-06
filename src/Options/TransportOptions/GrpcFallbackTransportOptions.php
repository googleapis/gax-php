<?php

namespace Google\ApiCore\Options\TransportOptions;

use ArrayAccess;
use Closure;
use Google\ApiCore\Options\OptionsTrait;

/**
 * The GrpcFallbackTransportOptions class provides typing to the associative array of options used
 * to configure {@see \Google\ApiCore\Transport\GrpcFallbackTransport}.
 */
class GrpcFallbackTransportOptions implements ArrayAccess
{
    use OptionsTrait;

    private ?Closure $clientCertSource;

    private ?Closure $httpHandler;

    /**
     * @param array $options {
     *    Config options used to construct the gRPC Fallback transport.
     *
     *    @type callable $clientCertSource
     *          A callable which returns the client cert as a string.
     *    @type callable $httpHandler
     *          A handler used to deliver PSR-7 requests.
     * }
     */
    public function __construct(array $options)
    {
        $this->fromArray($options);
    }

    /**
     * Sets the array of options as class properites.
     *
     * @param array $arr See the constructor for the list of supported options.
     */
    private function fromArray(array $arr): void
    {
        $this->setClientCertSource($arr['clientCertSource'] ?? null);
        $this->setHttpHandler($arr['httpHandler'] ?? null);
    }

    public function setHttpHandler(?callable $httpHandler)
    {
        if (!is_null($httpHandler)) {
            $this->httpHandler = Closure::fromCallable($httpHandler);
        }
        $this->httpHandler = $httpHandler;
    }

    /**
     * @param ?callable $clientCertSource
     */
    public function setClientCertSource(?callable $clientCertSource)
    {
        if (!is_null($clientCertSource)) {
            $this->clientCertSource = Closure::fromCallable($clientCertSource);
        }
        $this->clientCertSource = $clientCertSource;
    }
}
