<?php

namespace Google\ApiCore\Options;

use Google\ApiCore\ValidationException;
use BadMethodCallException;

/**
 * Trait implemented by any class representing an associative array of PHP options.
 * This provides validation and typehinting to loosely typed associative arrays.
 */
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

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * @throws BadMethodCallException
     */
    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('Cannot set options through array access. Use the setters instead');
    }

    /**
     * @throws BadMethodCallException
     */
    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('Cannot unset options through array access. Use the setters instead');
    }

    public function toArray(): array
    {
        $arr = [];
        foreach (get_object_vars($this) as $key => $value) {
            $arr[$key] = $value;
        }
        return $arr;
    }
}
