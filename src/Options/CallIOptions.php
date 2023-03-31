<?php

namespace Google\ApiCore\Options;

use ArrayAccess;

class CallOptions implements ArrayAccess
{
    use OptionsTrait;

    /**
     * @param array $options {
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
    }
}