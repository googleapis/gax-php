<?php

class B {
    public string $name;
}

class A
{
    public function test(?B $str): void
    {
        print($str);
    }
}

$a = new A();
$a->test(null);