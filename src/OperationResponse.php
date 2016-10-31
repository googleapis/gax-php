<?php

// Simple case
$op = $sampleApi->longRunningRpc();
// ... do stuff ...
$op->pollUntilComplete();
$result = $op->getResult();

// Save handle and resume
$opName = $sampleApi->longRunningRpc()->getName();
// ... do stuff ...
$op = $sampleApi->getOperationsApi()->getOperation($opName);
$op->pollUntilComplete();
$result = $op->getResult();

// Polling loop
$op = $sampleApi->longRunningRpc();
while (!$op->isDone()) {
    // ... do stuff ...
    $op->refresh();
}
$result = $op->getResult();

// Polling loop with promise
$op = $sampleApi->longRunningRpc();
$op->promise()->then($funcToHandleResponse, $funcToHandleError);
while (true) {
    // ... do stuff ...
    $op->refresh();
}

// Sample using operation proto object
$op = $sampleApi->longRunningRpc();
// ... do stuff ...
$op->pollUntilComplete();
$opProto = $op->getProtoResponse();
// ... use opProto ...


//////////////////////////////////////////////////////////////////
// Samples when returning an array with flag for checking success
list($success, $response) = $sampleApi->longRunningRpc()->pollUntilComplete();
if ($success) {
    // handle success
} else {
    // handle failure
}

list($statusCode, $response) = $sampleApi->longRunningRpc()->pollUntilComplete();
if ($statusCode == Google\Rpc\Code::OK) {
    // handle success
} else {
    // handle failure
}


class OperationResponse
{
    private $operationProto;
    private $operationsApi;

    // OPTIONAL: support promises using a deferred object using
    // https://github.com/reactphp/promise#deferred or similar
    private $deferred;

    public function isDone()
    {
        return $this->operationProto->getDone();
    }

    public function getName()
    {
        return $this->operationProto->getName();
    }

    public function pollUntilComplete($pollSettings = [])
    {
        while (!$this->isDone()) {
            // TODO: use poll settings
            sleep(1);
            $this->refresh();
        }
    }

    // OPTIONAL: provide a promise (using https://github.com/reactphp/promise)
    public function promise() {
        return $this->deferred->promise();
    }

    public function refresh($resolvePromiseOnComplete = true)
    {
        $name = $this->getName();
        $this->operationsProto = $this->operationsApi->getOperation($name);

        // OPTIONAL: resolve promise
        if ($resolvePromiseOnComplete && $this->isDone()) {
            if ($this->operationsProto->hasError()) {
                $this->deferred->reject($this->operationsProto->getError());
            } elseif ($this->operationsProto->hasResponse()) {
                $this->deferred->resolve($this->operationsProto->getResponse());
            } else {
                throw new Exception("this should never happen");
            }
        }
    }

    public function getResult()
    {
        if (!$this->isDone()) {
            return null;
        }
        if ($this->operationsProto->hasError()) {
            // TODO: throw detailed exception
            $error = $this->operationsProto->getError();
            throw new Exception("$error");
        }
        if ($this->opertationsProto->hasResponse()) {
            // TODO: implement unpacking
            return unpack_response($this->operationsProto->getResponse());
        }
        throw new Exception("this should never happen...");
    }

    public function getProtoResponse()
    {
        return $this->operationProto;
    }

    public function cancel()
    {
        $this->operationsApi->cancelOperation($this->getName());
    }
}
