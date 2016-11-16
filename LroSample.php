<?php

$api = new SpeechApi();

$data = null;

$op = $api->asyncRecognize($data);
$result = $op->wait();

$op->

$op = $api->asyncRecognize($data);
// Do work...
while (!$op->isComplete()) {
    $result = $op->wait();
}
