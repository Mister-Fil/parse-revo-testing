#!/usr/bin/env php
<?php

declare(strict_types=1);

use Swoole\Coroutine\Client;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

require __DIR__ . '/vendor/autoload.php';

//$start = hrtime(true);
//$eta = round(hrtime(true) - $start) / 1e+0; // Наносекунда 1 / 1 000 000 000
//$eta = round(hrtime(true) - $start) / 1e+3; // Микросекунда 1 / 1 000 000 | Curl
//$eta = round(hrtime(true) - $start) / 1e+6; // Миллисекунда 1 / 1 000
//$eta = round(hrtime(true) - $start) / 1e+9; // Наносекунда 1 / 1
//var_dump('Время: ' . $eta . PHP_EOL);
//$response->write('Время: ' . $eta . ' микр.сек.' . PHP_EOL);

$table = new Swoole\Table(64);
$table->column('search', Swoole\Table::TYPE_STRING, 32);
$table->column('body', Swoole\Table::TYPE_STRING, 1000000);
$table->create();
//$table->destroy();

$server = new Server("0.0.0.0", 9555);
$server->set(array(
    'worker_num' => 64,
    'task_worker_num' => 128,
//    'enable_coroutine' => true,
    'task_enable_coroutine' => true,
));

$server->on(
    "request",
    function (Request $request, Response $response) use ($server, $table) {
        $start = hrtime(true);
        if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
            $response->end();
            return;
        }

        $path = explode('/', trim($request->server['request_uri'], '/'));
        switch ($path[0]) {
            case 'sites':
                if (!empty($request->get['search'])) {
                    $response->header('Content-Type', 'application/json');
                    $headers = [
                        'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4467.0 Safari/537.36',
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                        'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,zh-TW;q=0.6,zh;q=0.5',
                    ];

                    $hosts = (new Parser\Serp)->parseYandex($request, $table, $headers);

                    $checkListDomain = Benchmark\Growth::checkDomainMultiple($server, $hosts, $headers);

                    $response->write(json_encode($checkListDomain));

                    break;
                }
            default:
                $response->header('Content-Type', 'text/html; charset=utf-8');
                $response->status(404);
                break;
        }
        $response->end('{"error": true}');
    }
);

$server->on('Task', function (Swoole\Server $server, $task) {
    // {"method": String, "params": {"site": String, "headers": Array}, "id": String}
    $json_rpc = $task->data;

    // {"result": Mixed, "id": String}
    $result = [
        'result' => null,
        'id' => $json_rpc['id'],
    ];

    switch ($json_rpc['method']) {
        case 'checkDomain':
            $client = new Client(SWOOLE_SOCK_TCP);
            if (!$client->connect('127.0.0.1', 9560, 0.5)) {
                echo "connect failed. Error: {$client->errCode}\n";
            }
            $client->send(json_encode($json_rpc));
            $recv = $client->recv(28.0);
            if (!empty($recv) && $recv != '') {
                $result['result'] = json_decode($recv, true)['result'];
            }
            $client->close();
            break;
    }

    $task->finish($result);
});

$server->start();





