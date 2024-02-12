<?php

namespace Google\ApiCore\Tests\Unit;

use Google\ApiCore\ClientInterface;

class CustomOperationsClient implements ClientInterface
{
    public function getOperation($name, $arg1, $arg2)
    {
    }
}
