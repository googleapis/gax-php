<?php

namespace Google\ApiCore;

use Google\LongRunning\Client\OperationsClient;

interface LongRunningClientInterface
{
    public function resumeOperation(
        string $operationName,
        ?string $methodName = null
    ): OperationResponse;

    public function getOperationsClient(): OperationsClient;
}
