<?php

namespace Google\ApiCore\Options\TransportOptions;

use ArrayAccess;
use Google\ApiCore\Options\OptionsTrait;
use Closure;

class GrpcTransportOptions implements ArrayAccess
{
    use OptionsTrait;

    private array $stubOpts;

    private ?Channel $channel;

    /**
     * @var Interceptor[]|UnaryInterceptorInterface[]
     */
    private array $interceptors;

    private ?Closure $clientCertSource;

    /**
     * @param array $options {
     *    Config options used to construct the gRPC transport.
     *
     *    @type array $stubOpts Options used to construct the gRPC stub.
     *    @type Channel $channel Grpc channel to be used.
     *    @type Interceptor[]|UnaryInterceptorInterface[] $interceptors *EXPERIMENTAL*
     *          Interceptors used to intercept RPC invocations before a call starts.
     *          Please note that implementations of
     *          {@see \Google\ApiCore\Transport\Grpc\UnaryInterceptorInterface} are
     *          considered deprecated and support will be removed in a future
     *          release. To prepare for this, please take the time to convert
     *          `UnaryInterceptorInterface` implementations over to a class which
     *          extends {@see Grpc\Interceptor}.
     *    @type callable $clientCertSource A callable which returns the client cert as a string.
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
        $this->setStubOpts($arr['stubOpts'] ?? []);
        $this->setChannel($arr['channel'] ?? null);
        $this->setInterceptors($arr['interceptors'] ?? []);
        $this->setClientCertSource($arr['clientCertSource'] ?? null);
    }

    /**
     * @param array $stubOpts
     */
    public function setStubOpts(array $stubOpts)
    {
        $this->stubOpts = $stubOpts;
    }

    /**
     * @param ?Channel $channel
     */
    public function setChannel(?Channel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * @param Interceptor[]|UnaryInterceptorInterface[] $interceptors
     */
    public function setInterceptors(array $interceptors)
    {
        $this->interceptors = $interceptors;
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