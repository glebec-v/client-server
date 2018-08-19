#!/usr/bin/env php
<?php

require __DIR__. '/vendor/autoload.php';

$params = getopt('', ['address::', 'port::', 'message:']);
$message = $params['message'] ?? '()';
unset($params['message']);

echo 'message:'.$message.PHP_EOL;

$client = new \GlebecV\Network\Client(null, $params);

try {
    echo $client->send($message);
} catch (\Exception $exception) {
    $error = $exception->getMessage().PHP_EOL.$exception->getTraceAsString();
    echo "Socket connection error: {$error}".PHP_EOL;
}