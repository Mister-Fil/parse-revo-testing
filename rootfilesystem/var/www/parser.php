#!/usr/bin/env php
<?php

declare(strict_types=1);

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$http = new Server("0.0.0.0", intval($_ENV['HTTP_PORT'] ?? 9501));

$http->on(
    "request",
    function (Request $request, Response $response) use ($http) {
        $response->end("Hello, World Parser!\n");
    }
);

$http->start();
