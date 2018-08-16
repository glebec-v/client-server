#!/usr/bin/env php
<?php

$params = getopt('', ['address::', 'port::', 'threads:']);
$address = $params['address'] ?? '127.0.0.1';
$port    = $params['port']    ?? 9999;
$threads = $params['threads'] ?? 1;

$acceptor = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

if (false === $acceptor) {
    die("Socket create failed: ".socket_strerror(socket_last_error()).PHP_EOL);
}
socket_set_option($acceptor, SOL_SOCKET, SO_REUSEADDR, 1); // для снятия блокировки после предыдущего пользования сокетом
if (!socket_bind($acceptor, $address, $port)) {
    die("Socket bind failed: ".socket_strerror(socket_last_error()).PHP_EOL);
}
if (!socket_listen($acceptor, 1)) {
    die("Socket listen failed: ".socket_strerror(socket_last_error()).PHP_EOL);
}

for ($i = 0; $i < $threads; $i++) {
    $pid = pcntl_fork();
    if (0 === $pid) {
        // child process
        while (true) {
            $socket = socket_accept($acceptor);
            echo "Accepted {$socket}".PHP_EOL;

            $pid = posix_getpid();
            socket_write($socket, "Accepted by process {$pid}".PHP_EOL);

            $peerAddr = '';
            $peerPort = 0;
            $command = trim(socket_read($socket, 2048));
            socket_getpeername($socket, $peerAddr, $peerPort);
            $peerAddr .= ':'.(0 !== $peerPort ?: '');
            echo "Got command {$command} from {$peerAddr}".PHP_EOL;

            socket_write($socket, "[{$command}]".PHP_EOL);

            socket_close($socket);
        }
    }
}

while (-1 !== ($cid = pcntl_waitpid(0, $status) )) {
    $exitCode = pcntl_wexitstatus($status);
    echo "Child process exited with code {$exitCode}".PHP_EOL;
}

socket_close($acceptor);
