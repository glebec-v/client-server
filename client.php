#!/usr/bin/env php
<?php

$params = getopt('', ['address::', 'port::', 'message:']);
$address = $params['address'] ?? '127.0.0.1';
$port    = $params['port']    ?? 9999;
// $message = $params['message'] ?? "GET /\n";

while (true) {
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (false === $socket) {
        die("Socket create failed: ".socket_strerror(socket_last_error()).PHP_EOL);
    }

    usleep(100000);

    $message = (string)mt_rand(10000, 99999).PHP_EOL;

    if (false === socket_connect($socket, $address, $port)) {
        die("Socket connect failed: ".socket_strerror(socket_last_error()).PHP_EOL);
    }
    socket_write($socket, $message, strlen($message));

    $response = '';
    while ('' !== ($chunk = socket_read($socket, 2048))) {
        $response .= $chunk;
    }
    echo $response.PHP_EOL;

    socket_close($socket);
}