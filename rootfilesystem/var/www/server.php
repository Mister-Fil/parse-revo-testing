#!/usr/bin/env php
<?php

declare(strict_types=1);

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

require __DIR__ . '/vendor/autoload.php';

$http = new Server("0.0.0.0", intval($_ENV['HTTP_PORT'] ?? 9501));

$table = new Swoole\Table(64);
//$table->column('id_search_md5', Swoole\Table::TYPE_STRING, 32);
$table->column('search', Swoole\Table::TYPE_STRING, 32);
$table->column('body', Swoole\Table::TYPE_STRING, 1000000);
$table->create();

$http->set(array(
    'worker_num' => 32,
    'task_worker_num' => 256,
));

$http->on(
    "request",
    function (Request $request, Response $response) use ($http, $table) {
        $start = microtime(true);
//                    $response->write('Время: ' . number_format(microtime(true) - $start, 6, '.', '') . ' сек.' . PHP_EOL);
        if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
            $response->end();
            return;
        }

//        $http->send($fd, "分发任务，任务id为$task_id\n");

//        $response->end($table->memorySize / 1024 / 1024);
//        $table->destroy();
//        return;

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
                    $result = (new Parser\Serp)->parseYandex($request, $response, $table);
                    $response->write('Время RAW: ' . microtime(true) - $start . ' сек.' . PHP_EOL);
                    $response->write('Время number_format: ' . number_format(microtime(true) - $start, 6, '.', '') . ' сек.' . PHP_EOL);

//                    $results = $http->taskWaitMulti($tasks, 4.0);
//                    $response->write(var_export($results, true) . PHP_EOL);
//                    $checkListDomain = (new Benchmark\Growth)->checkListDomains($http, $result);
                    $start = microtime(true);
                    $checkListDomain = Benchmark\Growth::checkDomain('https://api-dev.skladskoi.com/', $headers);
                    $response->write('Время checkDomain: ' . round(microtime(true) - $start, 6) . ' сек.' . PHP_EOL);
                    $response->write('Время RAW: ' . microtime(true) - $start . ' сек.' . PHP_EOL);

                    $response->write(var_export($checkListDomain, true) . PHP_EOL);
                    $response->write(var_export($checkListDomain['total_time_us'] <= 3000000, true) . PHP_EOL);
                    $response->write('Время: ' . microtime(true) - $start . ' сек.' . PHP_EOL);
                    break;
                }
            default:
                $response->status(404);
                break;
        }
        $response->end('<h1>No Data</h1>');
    }
);

$http->on('Task', function (Swoole\Server $server, $task_id, $reactor_id, $data) {
    echo "Tasker получает данные" . PHP_EOL;
    sleep(3);
    echo "#{$server->worker_id}\tonTask: [PID={$server->worker_pid}]: task_id=$task_id, data_len=" . print_r($data, true) . "." . PHP_EOL;
    $server->finish(1);
});

$http->start();


