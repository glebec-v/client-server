#!/usr/bin/env php
<?php

require __DIR__. '/vendor/autoload.php';

$params = getopt('', ['address::', 'port::', 'threads:']);

$server = new \GlebecV\Network\Server(\GlebecV\BracketsCounter::class, $params);

$server->run();

