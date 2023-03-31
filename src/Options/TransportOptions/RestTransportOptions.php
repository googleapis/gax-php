<?php

namespace Google\ApiCore\Options\TransportOptions;

use ArrayAccess;
use Closure;
use Google\ApiCore\Options\OptionsTrait;

class RestTransportOptions implements ArrayAccess
{
    use OptionsTrait;

    private ?Closure $httpHandler;

    private ?Closure $clientCertSource;

    private ?string $restClientConfigPath;

    /**
     * @param array $options {
     *    Config options used to construct the REST transport.
     *
     *    @type callable $httpHandler
     *          A handler used to deliver PSR-7 requests.
     *    @type callable $clientCertSource
     *          A callable which returns the client cert as a string.
     *    @type string $restClientConfigPath
     *          The path to the REST client config file.
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
        $this->setHttpHandler($arr['httpHandler'] ?? null);
        $this->setClientCertSource($arr['clientCertSource'] ?? null);
        $this->setRestClientConfigPath($arr['restClientConfigPath'] ?? null);
    }

    /**
     * @param ?callable $httpHandler
     */
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

    /**
     * @param ?string $restClientConfigPath
     */
    public function setRestClientConfigPath(?string $restClientConfigPath)
    {
        $this->restClientConfigPath = $restClientConfigPath;
    }
}
