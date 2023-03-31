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
        throw new BadMethodCallException('Cannot set options through array access. Use the setters instead');
    }

    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('Cannot unset options through array access. Use the setters instead');
    }

    public function toArray(): array
    {
        $arr = [];
        /** @phpstan-ignore-next-line */
        foreach ($this as $key => $value) {
            $arr[$key] = $value;
        }
        return $arr;
    }
}
