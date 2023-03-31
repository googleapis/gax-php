<?php

namespace Google\ApiCore\Options;

use Google\ApiCore\ValidationException;
use BadMethodCallException;

trait OptionsTrait
{
    /**
     * @param string $filePath
     * @throws ValidationException
     */
    private static function validateFileExists(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new ValidationException("Could not find specified file: $filePath");
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * @throws BadMethodCallException
     */
    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('Options are read-only');
    }

    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('Options are read-only');
    }

    public function toArray(): array
    {
        $arr = [];
        foreach ($this as $key => $value) {
            $arr[$key] = $value;
        }
        return $arr;
    }
}