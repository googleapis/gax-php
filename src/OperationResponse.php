<?php

namespace Google\GAX;

use Google\Longrunning\OperationsClient;

/**
 * Response object from a long running API method
 *
 * The OperationResponse object is returned by API methods that perform
 * a long running operation. It provides methods that can be used to
 * poll the status of the operation, retrieve the results, and cancel
 * the operation.
 *
 * To support a long running operation, the server must implement the
 * Operations API, which is used by the OperationResponse object. If
 * more control is required, it is possible to make calls against the
 * Operations API directly instead of via the OperationResponse object
 * using an OperationsClient instance.
 */
class OperationResponse
{
    const DEFAULT_POLLING_INTERVAL = 1.0;

    private $operationName;
    private $operationsClient;
    private $operationReturnType;
    private $lastProtoResponse;

    public function __construct($operationName, $operationsClient, $options = [])
    {
        $this->operationName = $operationName;
        $this->operationsClient = $operationsClient;
        if (isset($options['operationReturnType'])) {
            $this->operationReturnType = $options['operationReturnType'];
        }
        if (isset($options['lastProtoResponse'])) {
            $this->lastProtoResponse = $options['lastProtoResponse'];
        }
    }

    public function isDone()
    {
         return is_null($this->lastProtoResponse)
             ? false
             : $this->lastProtoResponse->getDone();
    }

    public function getName()
    {
        return $this->operationName;
    }

    public function pollUntilComplete($handler = null, $pollSettings = [])
    {
        $defaultPollSettings = [
            'pollingIntervalSeconds' => this::DEFAULT_POLLING_INTERVAL,
        ];
        $pollSettings = array_merge($defaultPollSettings, $pollSettings);

        $pollingIntervalMicros = $pollSettings['pollingIntervalSeconds'] * 1000000;

        while (!$this->isDone()) {
            usleep($pollingIntervalMicros);
            $this->refresh();
        }

        if (!is_null($handler)) {
            return $handler($this);
        }

        return $this->lastProtoResponse->hasResponse();
    }

    public function refresh()
    {
        $name = $this->getName();
        $this->lastProtoResponse = $this->operationsClient->getOperation($name);
    }

    public function getResult()
    {
        if (!$this->isDone() || !$this->lastProtoResponse->hasResponse()) {
            return null;
        }

        $anyResponse = $this->lastProtoResponse->getResponse();
        if (is_null($this->operationReturnType)) {
            return $anyResponse;
        }
        $operationReturnType = $this->operationReturnType;
        $response = new $operationReturnType();
        $response->parse($anyResponse->getValue());
        return $response;
    }

    public function getError()
    {
        if (!$this->isDone() || !$this->lastProtoResponse->hasError()) {
            return null;
        }
        return $this->lastProtoResponse->getError();
    }

    public function getLastProtoResponse()
    {
        return $this->lastProtoResponse;
    }

    public function getOperationsClient()
    {
        return $this->operationsClient;
    }

    public function cancel()
    {
        $this->operationsClient->cancelOperation($this->getName());
    }
}
