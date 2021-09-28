#!/usr/bin/env php
<?php

declare(strict_types=1);

use Swoole\Coroutine as Co;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

require __DIR__ . '/vendor/autoload.php';

//Swoole\Runtime::enableCoroutine();
Co::set(['hook_flags' => SWOOLE_HOOK_ALL | SWOOLE_HOOK_CURL]);

$table = new Swoole\Table(64);
$table->column('search', Swoole\Table::TYPE_STRING, 32);
$table->column('body', Swoole\Table::TYPE_STRING, 1000000);
$table->create();
//$table->destroy();

$server = new Server("0.0.0.0", intval($_ENV['HTTP_PORT'] ?? 9501));
$server->set(array(
    'worker_num' => 16,
    'task_worker_num' => 64,
    'enable_coroutine' => true,
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
//                    $eta = (int)round((hrtime(true) - $start) / 1e+3); // Микросекунда 1 / 1 000 000 | Curl
//                    $response->write('Время parseYandex IN: ' . $eta . ' микр.сек.' . PHP_EOL);
                    $Hosts = (new Parser\Serp)->parseYandex($request, $response, $table);
//                    $eta = (int)round((hrtime(true) - $start) / 1e+3); // Микросекунда 1 / 1 000 000 | Curl
//                    $response->write('Время parseYandex OUT: ' . $eta . ' микр.сек.' . PHP_EOL);
//                    $response->write(var_export($Hosts, true));


//                    $eta = round(hrtime(true) - $start) / 1e+0; // Наносекунда 1 / 1 000 000 000
//                    $eta = round(hrtime(true) - $start) / 1e+3; // Микросекунда 1 / 1 000 000 | Curl
//                    $eta = round(hrtime(true) - $start) / 1e+6; // Миллисекунда 1 / 1 000
//                    $eta = round(hrtime(true) - $start) / 1e+9; // Наносекунда 1 / 1
//                    $response->write('Время: ' . $eta . PHP_EOL);

//                    $checkListDomain = (new Benchmark\Growth)->checkSite($server, 'https://api-dev.skladskoi.com/api/rack/rack', $headers, $response);
//                    $checkListDomain = (new Benchmark\Growth)->checkSite($server, 'https://api-dev.skladskoi.com/', $headers, $response);
                    $checkListDomain = (new Benchmark\Growth)->checkDomainMultiple($server, $Hosts, $headers);

                    $response->write(print_r($checkListDomain, true) . PHP_EOL);

                    break;
                }
            default:
                $response->status(404);
                break;
        }
        $response->end('No Data');
    }
);

$server->on('Task', function (Swoole\Server $server, $task_id, $reactor_id, $json_rpc) {
    // "jsonrpc": "2.0",
    // {"method": String, "params": {"site": String, "headers": Array}, "id": String}
    $result = [
        'result' => null,
        'id' => $json_rpc['id'],
    ];
    switch ($json_rpc['method']) {
        case 'getSiteInfo':
            $siteInfo = Benchmark\Growth::getSiteInfo($json_rpc['params']['site'], $json_rpc['params']['headers']);
            $result['result'] = $siteInfo;
            break;
        case 'checkDomain':
            $checkDomain = Benchmark\Growth::checkDomain($server, $json_rpc['params']['site'], $json_rpc['params']['headers']);
            $result['result'] = $checkDomain;
            break;
    }
    // "jsonrpc": "2.0",
    // {"result": Mixed, "id": String}
    $server->finish($result);
});

$server->start();


$serv = new Swoole\Server("127.0.0.1", 9510); // , SWOOLE_PROCESS, SWOOLE_SOCK_UDP

$serv->on('Connect', function ($serv, $fd) {
    $redis = new Redis();
    $redis->connect("127.0.0.1", 6379);
    Co::sleep(5);
    $redis->set($fd, "fd $fd connected");
});

$serv->on('Receive', function ($serv, $fd, $reactor_id, $data) {
    $redis = new Redis();
    $redis->connect("127.0.0.1", 6379);
    var_dump($redis->get($fd));
});

$serv->on('Close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

$serv->start();




