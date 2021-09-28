#!/usr/bin/env php
<?php

declare(strict_types=1);

use Swoole\Coroutine as Co;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

require __DIR__ . '/vendor/autoload.php';

Co::set(['hook_flags' => SWOOLE_HOOK_ALL]);

$server = new Server("0.0.0.0", intval($_ENV['HTTP_PORT'] ?? 9501));

$server->set([
//    'max_conn' => 50000,
//    'max_request' => 0,
//    'dispatch_mode' => 2,
//    'user'=>'www',
//    'group'=>'www',
    'worker_num' => 2, // Количество запускаемых рабочих процессов
    'task_worker_num' => 8,  // Количество рабочих задач для запуска
//    'backlog' => 512,       // TCP backlog connection number
]);
//$server->on('Task', function (swoole_server $serv, $task_id, $worker_id, $data) {
//    echo "#{$serv->worker_id}\tonTask: worker_id={$worker_id}, task_id=$task_id" . PHP_EOL;
//    sleep(1);
//    return hrtime(true);
//});

$server->on(
    "request",
    function (Request $request, Response $response) use ($server) {
        $tasks = [];
        $tasks[0] = "hello world";
        $tasks[1] = ['data' => 1234, 'code' => 200];
        $tasks[2] = "hello world";
        $tasks[3] = ['data' => 1234, 'code' => 200];
        $result = $server->taskCo($tasks, 1.5);
        dump($result, true);
        $response->end('Test End, Result: ' . print_r($result, true));

//        $response->end();

//        $tasks = [];
//        $tasks[] = "hello world";
//        $tasks[] = ['data' => 1234, 'code' => 200];
//        $tasks[] = time();
//        $result = $server->taskCo($tasks, 10);
//        $response->end('Test End, Result: ' . var_export($result, true));

//        $tasks[0] = ['time' => 0];
//        $tasks[1] = ['data' => 'www.swoole.co.uk', 'code' => 200];
//
//        $result = $server->taskCo($tasks, 1.5);
//
//        $response->end('Task Result: ' . var_export($result, true));

//        if ($request->get) {
//            $response->write('<h1>TEST: </h1>');
//            foreach ($request->get as $key => $value) {
//                switch ($key) {
//                    case 'search':
//                        $baseYandexURL = "servers://yandex.ru/search/touch/?service=www.yandex&ui=webmobileapp.yandex&numdoc=50&lr=213&p=0&text={$request->get['search']}";
//                        $response->write($baseYandexURL);
//                        break;
//                    case 'start':
//                        echo "{$value} \n";
//                        break;
//
//                }
//            }
//        }
//
//        $response->end();
//        $response->end("<h1>TEST: </h1>" . $baseYandexURL ."\n".PHP_EOL;);
    }
);

$server->on('Task', function (Swoole\Server $serv, $task_id, $worker_id, $data) {
    echo "#{$serv->worker_id}\tonTask: worker_id={$worker_id}, task_id=$task_id\n";
    if ($serv->worker_id == '23') {
        sleep(2);
    }
    return $data;
});


$server->start();

//class sites
//{
//    static function index($req, $resp)
//    {
//        echo "hello world";
//    }
//}
