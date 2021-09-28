#!/usr/bin/env php
<?php

declare(strict_types=1);


require __DIR__ . '/vendor/autoload.php';

$server = new Swoole\Server('127.0.0.1', 9502);
$server->set(array(
    'worker_num' => 64,
    'task_worker_num' => 512,
//    'enable_coroutine' => true,
    'task_enable_coroutine' => true,
));

$server->on('Connect', function ($server, $fd) {
//    echo "Client: Connect.\n";
});

$server->on('Receive', function ($server, $fd, $reactor_id, $data) {
    $json_rpc = json_decode($data, true);
    $result = [
        'result' => null,
        'id' => $json_rpc['id'],
    ];
    $checkDomain = Benchmark\Growth::checkDomain($server, $json_rpc['params']['site'], $json_rpc['params']['headers']);
    $result['result'] = $checkDomain;
    $server->send($fd, json_encode($result));
});

$server->on('Close', function ($server, $fd) {
//    echo "Client: Close.\n";
});

$server->on('Task', function (Swoole\Server $server, $task) {
    // {"method": String, "params": {"site": String, "headers": Array}, "id": String}
    $json_rpc = $task->data;

    // {"result": Mixed, "id": String}
    $result = [
        'result' => null,
        'id' => $json_rpc['id'],
    ];

    switch ($json_rpc['method']) {
        case 'getSiteInfo':
            $siteInfo = Benchmark\Growth::getSiteInfo($json_rpc['params']['site'], $json_rpc['params']['headers']);
            $result['result'] = $siteInfo;
            break;
    }

    $task->finish($result);
});

$server->start();
