<?php

namespace Benchmark;

use Swoole\Http\Server;
use Swoole\Coroutine as Co;

class Growth
{

    private array $httpCode2xx = [
        "200" => true,
        "201" => true,
        "202" => true,
        "203" => true,
        "204" => true,
        "205" => true,
        "206" => true,
        "207" => true,
        "208" => true,
        "226" => true,
    ];

    /**
     * @var array|int[]
     */
    private array $RequestThrottle = [];

    /**
     * @param $site
     * @param $headers
     * @return array
     */
    public static function getSiteInfo($site, $headers): array
    {
//        dump($site);
        $return = [
            'total_time_us' => 0,
            'http_code' => 500,
            'site' => $site,
        ];
        $curl = curl_init();
        $curl_options = [
            CURLOPT_URL => $site,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => false,
        ];
        curl_setopt_array($curl, $curl_options);

//        if (curl_errno($curl)) {
        if (curl_exec($curl) === false) {
//            dump(curl_error($curl));
            curl_close($curl);
            return $return;
        }

        $curl_info = curl_getinfo($curl);
//        dump($curl_info);

        curl_close($curl);

        $return['http_code'] = (int)$curl_info['http_code'];
        if ($return['http_code'] != 200) {
            return $return;
        }

//        return $curl_info;
        $return['total_time_us'] = (int)$curl_info['total_time_us'];
        return $return;
    }

    public function checkDomainMultiple(Server $server, $domains, $headers)
    {
        Co::set(['hook_flags' => SWOOLE_HOOK_ALL]);
//        Swoole\Runtime::enableCoroutine();
        $domains = [
            'https://api-dev.skladskoi.com/?test=',
            'https://api-dev.skladskoi.com/api/rack/rack?test=',
            'https://api-dev.skladskoi.com/?test=',
            'https://api-dev.skladskoi.com/api/rack/rack?test=',
            'https://api-dev.skladskoi.com/?test=',
            'https://api-dev.skladskoi.com/api/rack/rack?test=',
            'https://api-dev.skladskoi.com/?test=',
            'https://api-dev.skladskoi.com/api/rack/rack?test=',
        ];
        $tasks = [];
        $i = 0;
        foreach ($domains as $domain) {
            ++$i;
//            if ($i > 5) break;
//            dump($domain);
//            $site = ($domain['Scheme'] ?? 'https') . '://' . $domain['Host'] . '/';
//            $site = 'https://api-dev.skladskoi.com/?' . $i;
            $site = $domain . $i;
            if (!array_key_exists($site, $this->RequestThrottle)) {
                $this->RequestThrottle[$site] = 0;
                $tasks[] = [
                    'method' => 'checkDomain',
                    'params' => [
                        'site' => $site,
                        'headers' => $headers,
                    ],
                    'id' => $site,
                ];
            }
        }
        foreach (array_chunk($tasks, 5, true) as $task) {
            dump($task);
            foreach ($task as $site => $result) {
//            $results = $server->taskWaitMulti($task, 10);
//            dump($results);
//            $results = $server->taskWaitMulti($task, 10);
//                dump($site);
//                dump($result);
            }
//            dump($results);
        }
        return $this->RequestThrottle;
    }

    public static function checkDomain(Server $server, $site, $headers)
    {
        $tasks = [];
        $count = 0;
//        if (!array_key_exists($site, $this->RequestThrottle)) {
//            $this->RequestThrottle[$site] = 0;
        $i = 0;
        do {
            // {"method": String, "params": {"site": String, "headers": Array}, "id": String}
            $tasks[] = [
                'method' => 'getSiteInfo',
                'params' => [
                    'site' => $site,
                    'headers' => $headers,
                ],
                'id' => $site,
            ];
//                $results = $server->taskWaitMulti($tasks, 4.0);
            dump($tasks);
            $results = $server->taskCo($tasks, 3.5);
            dump($results);
            if ($next = Growth::checkResponses($results)) {
                $count = count($results);
//                    $this->RequestThrottle[$site] = [count($results), $results];
            } else {
                dump($tasks);
            }
        } while ($next && ++$i <= 10);
        return $count;
//        }
    }

    public static function checkResponses($resultTasks)
    {
        dump($resultTasks);
        // "jsonrpc": "2.0",
        // {"result": Mixed, "id": String}
        $count = count($resultTasks);
        foreach ($resultTasks as $resultTask) {
            if (
                empty($resultTask['result'])
                || empty($resultTask['result']['total_time_us'])
                || $resultTask['result']['total_time_us'] > 3000000
            ) {
                return false;
            }
        }
        return $count > 0;
    }
}