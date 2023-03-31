<?php

namespace Google\ApiCore\Options;

use Google\ApiCore\Options\TransportOptions\GrpcTransportOptions;
use Google\ApiCore\Options\TransportOptions\GrpcFallbackTransportOptions;
use Google\ApiCore\Options\TransportOptions\RestTransportOptions;
use ArrayAccess;

class TransportOptions implements ArrayAccess
{
    use OptionsTrait;

    private GrpcTransportOptions $grpc;

    private GrpcFallbackTransportOptions $grpcFallback;

    private RestTransportOptions $rest;

    /**
     * @param array $options {
     *    Config options used to construct the transport.
     *
     *    @type array $grpc
     *    @type array $grpcFallback
     *    @type array $rest
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
        $this->setGrpc($arr['grpc'] ?? []);
        $this->setGrpcFallback($arr['grpc-fallack'] ?? []);
        $this->setRest($arr['rest'] ?? []);
    }

    public function setGrpc(array $grpc): void
    {
        $this->grpc = new GrpcTransportOptions($grpc);
    }

    public function setGrpcFallback(array $grpcFallback): void
    {
        $this->grpcFallback = new GrpcFallbackTransportOptions($grpcFallback);
    }

    public function setRest(array $rest): void
    {
        $this->rest = new RestTransportOptions($rest);
    }
}
