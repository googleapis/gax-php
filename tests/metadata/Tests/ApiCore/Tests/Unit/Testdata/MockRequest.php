<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: tests/ApiCore/Tests/Unit/testdata/mock_request.proto

namespace GPBMetadata\Tests\ApiCore\Tests\Unit\Testdata;

class MockRequest
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        $pool->internalAddGeneratedFile(hex2bin(
            "0a90010a3474657374732f417069436f72652f54657374732f556e69742f" .
            "74657374646174612f6d6f636b5f726571756573742e70726f746f121a67" .
            "6f6f676c652e617069436f72652e74657374732e6d6f636b7322340a0b4d" .
            "6f636b5265717565737412120a0a706167655f746f6b656e180120012809" .
            "12110a09706167655f73697a65180220012804620670726f746f33"
        ));

        static::$is_initialized = true;
    }
}

