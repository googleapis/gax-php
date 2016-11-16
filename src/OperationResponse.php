<?php

namespace Google\GAX;

use Google\Longrunning\OperationsApi;

class OperationResponse
{
    private $operationName;
    private $operationsApi;
    private $operationReturnType;
    private $lastProtoResponse;

    public static function resumeOperation($operationName, $serviceAddress)
    {
        $operationsApi = new OperationsApi(['serviceAddress' => $serviceAddress]);
        $longRunningDescriptor = [
            'operationsApi' => $operationsApi,
            'operationReturnType' => null,
        ];
        return new OperationResponse($operationName, $longRunningDescriptor, null);
    }

    public function __construct($operationName, $longRunningDescriptor, $lastProtoResponse = null)
    {
        $this->operationName = $operationName;
        $this->operationsApi = $longRunningDescriptor['operationsApi'];
        $this->operationReturnType = $longRunningDescriptor['operationReturnType'];
        $this->lastProtoResponse = $lastProtoResponse;
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
        while (!$this->isDone()) {
            // TODO: use poll settings
            sleep(1);
            echo "refreshing...\n";
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
        $this->lastProtoResponse = $this->operationsApi->getOperation($name);
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

    public function cancel()
    {
        $this->operationsApi->cancelOperation($this->getName());
    }
}
