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
    private $metadataReturnType;
    private $lastProtoResponse;
    private $deleted = false;

    public function __construct($operationName, $operationsClient, $options = [])
    {
        $this->operationName = $operationName;
        $this->operationsClient = $operationsClient;
        if (isset($options['operationReturnType'])) {
            $this->operationReturnType = $options['operationReturnType'];
        }
        if (isset($options['metadataReturnType'])) {
            $this->metadataReturnType = $options['metadataReturnType'];
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
         return (is_null($this->lastProtoResponse) || is_null($this->lastProtoResponse->getDone()))
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
        if ($this->deleted) {
            throw new ValidationException("Cannot call reload() on a deleted operation");
        }
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
     * Starts asynchronous cancellation on a long-running operation. The server
     * makes a best effort to cancel the operation, but success is not
     * guaranteed. If the server doesn't support this method, it will throw an
     * ApiException with code \google\rpc\Code::UNIMPLEMENTED. Clients can continue
     * to use reload and pollUntilComplete methods to check whether the cancellation
     * succeeded or whether the operation completed despite cancellation.
     * On successful cancellation, the operation is not deleted; instead, it becomes
     * an operation with a getError() value with a \google\rpc\Status code of 1,
     * corresponding to \google\rpc\Code::CANCELLED.
     */
    public function cancel()
    {
        $this->operationsClient->cancelOperation($this->getName());
    }

    /**
     * Delete the long-running operation. This method indicates that the client is
     * no longer interested in the operation result. It does not cancel the operation.
     * If the server doesn't support this method, it will throw an ApiException with
     * code google\rpc\Code::UNIMPLEMENTED.
     */
    public function delete()
    {
        $this->operationsClient->deleteOperation($this->getName());
        $this->deleted = true;
    }

    /**
     * Get the metadata returned with the last proto response. If a metadata type was provided, then
     * the return value will be of that type - otherwise, the return value will be of type Any. If
     * no metadata object is available, returns null.
     *
     * @return mixed The metadata returned from the server in the last response.
     */
    public function getMetadata()
    {
        if (is_null($this->lastProtoResponse)) {
            return null;
        }
        $any = $this->lastProtoResponse->getMetadata();
        if (is_null($this->metadataReturnType)) {
            return $any;
        }
        if (is_null($any) || is_null($any->getValue())) {
            return null;
        }
        $metadataReturnType = $this->metadataReturnType;
        $metadata = new $metadataReturnType();
        $metadata->parse($any->getValue());
        return $metadata;
    }
}
