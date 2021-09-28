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

        $response->header('Content-Type', 'text/html; charset=utf-8');

        $path = explode('/', trim($request->server['request_uri'], '/'));
        switch ($path[0]) {
            case 'sites':
                if (!empty($request->get['search'])) {
                    /**
                     * @var array|string[]
                     */
                    $headers = [
                        'User-Agent: Mozilla/5.0 (Linux; Android 5.0; SM-G900P Build/LRX21T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4467.0 Mobile Safari/537.36',
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                    ];
                    $response->write('Время parseYandex IN: ' . round((hrtime(true) - $start) / 1e+3) . ' микр.сек.' . PHP_EOL);
                    $Hosts = (new Parser\Serp)->parseYandex($request, $table);
                    $response->write('Время parseYandex OUT: ' . round((hrtime(true) - $start) / 1e+3) . ' микр.сек.' . PHP_EOL);
//                    $response->write(var_export($Hosts, true));

                    $response->write('Время checkDomainMultiple IN: ' . round((hrtime(true) - $start) / 1e+3) . ' микр.сек.' . PHP_EOL);
                    $checkListDomain = (new Benchmark\Growth)->checkDomainMultiple($server, $Hosts, $headers);
                    $response->write('Время checkDomainMultiple OUT: ' . round((hrtime(true) - $start) / 1e+3) . ' микр.сек.' . PHP_EOL);

                    $response->write(var_export($checkListDomain, true) . PHP_EOL);

                    break;
                }
            default:
                $response->status(404);
                break;
        }
        $response->write('Время END: ' . round((hrtime(true) - $start) / 1e+3) . ' микр.сек.' . PHP_EOL);
        $response->end('No Data');
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





