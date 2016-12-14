<?php

namespace Google\GAX;

/**
 * Response object from a long running API method.
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

    /**
     * Check whether the operation has completed.
     *
     * @return bool
     */
    public function isDone()
    {
         return is_null($this->lastProtoResponse)
             ? false
             : $this->lastProtoResponse->getDone();
    }

    /**
     * Get the formatted name of the operation
     *
     * @return string The formatted name of the operation
     */
    public function getName()
    {
        return $this->operationName;
    }

    /**
     * Poll the server in a loop until the operation is complete.
     *
     * If the $handler callable is not null, then call $handler (with $this as argument) and return
     * the result. Otherwise, return true if the operation completed successfully, otherwise return
     * false.
     *
     * The $pollSettings optional argument can be used to control the polling loop.
     *
     * @param callable $handler An optional callable which accepts $this as its argument. If not
     * null, $handler will be called once the operation is complete (when $this->isDone() is true).
     * @param array $pollSettings {
     *      An optional array to control the polling behavior.
     *      @type float $pollingIntervalSeconds The polling interval to use, in seconds.
     *                                          Default: 1.0
     * }
     * @return mixed
     */
    public function pollUntilComplete($handler = null, $pollSettings = [])
    {
        $defaultPollSettings = [
            'pollingIntervalSeconds' => $this::DEFAULT_POLLING_INTERVAL,
        ];
        $pollSettings = array_merge($defaultPollSettings, $pollSettings);

        $pollingIntervalMicros = $pollSettings['pollingIntervalSeconds'] * 1000000;

        while (!$this->isDone()) {
            usleep($pollingIntervalMicros);
            $this->reload();
        }

        if (!is_null($handler)) {
            return $handler($this);
        }

        return $this->lastProtoResponse->hasResponse();
    }

    /**
     * Reload the status of the operation with a request to the service.
     */
    public function reload()
    {
        $name = $this->getName();
        $this->lastProtoResponse = $this->operationsClient->getOperation($name);
    }

    /**
     * Return the result of the operation. If the operation is not complete, or if the operation
     * failed, return null.
     * @return mixed The result of the operation, or null if the operation failed or is not complete
     */
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

    /**
     * If the operation failed, return the status. If the operation succeeded or is not complete,
     * return null.
     *
     * @return \google\rpc\Status|null The status of the operation in case of failure, otherwise
     *                                 null.
     */
    public function getError()
    {
        if (!$this->isDone() || !$this->lastProtoResponse->hasError()) {
            return null;
        }
        return $this->lastProtoResponse->getError();
    }

    /**
     * @return \google\longrunning\Operation The last Operation object received from the server.
     */
    public function getLastProtoResponse()
    {
        return $this->lastProtoResponse;
    }

    /**
     * @return \Google\Longrunning\OperationsClient The OperationsClient object used to make
     * requests to the operations API.
     */
    public function getOperationsClient()
    {
        return $this->operationsClient;
    }

    /**
     * Cancel the operation.
     */
    public function cancel()
    {
        $this->operationsClient->cancelOperation($this->getName());
    }
}
