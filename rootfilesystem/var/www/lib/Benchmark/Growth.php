<?php

namespace Benchmark;

use Swoole\Http\Server as HttpServer;
use Swoole\Server;

class Growth
{

    /**
     * @var array|bool[]
     */
    private static array $httpCode2xx = [
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
     * @param string $site
     * @param array $headers
     * @return array
     */
    public static function getSiteInfo(string $site, array $headers): array
    {
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

        if (curl_exec($curl) === false) {
//            dump(curl_error($curl));
            curl_close($curl);
            return $return;
        }

        $curl_info = curl_getinfo($curl);

        curl_close($curl);

        $return['http_code'] = (int)$curl_info['http_code'];
        if ($return['http_code'] != 200) {
            return $return;
        }

        $return['total_time_us'] = (int)$curl_info['total_time_us'];
        return $return;
    }

    /**
     * @param Server $server
     * @param string $site
     * @param array $headers
     * @return int
     */
    public static function checkDomain(Server $server, string $site, array $headers): int
    {
        $tasks = [];
        $count = 0;
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
            $results = $server->taskCo($tasks, 3.5);
            if ($next = Growth::checkResponses($results)) {
                $count = count($results);
            }
        } while ($next && ++$i <= 20);
        return $count;
    }

    /**
     * @param array $resultTasks
     * @return bool
     */
    private static function checkResponses(array $resultTasks): bool
    {
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

    /**
     * @param HttpServer $server
     * @param array $hosts
     * @param array $headers
     * @return array
     */
    public static function checkDomainMultiple(HttpServer $server, array $hosts, array $headers): array
    {
        $tasks = [];
        $RequestThrottle = [];
        foreach ($hosts as $host) {
            $site = ($domain['Scheme'] ?? 'https') . '://' . $host['Host'] . '/';
            if (!array_key_exists($site, $RequestThrottle)) {
                $RequestThrottle[$site] = 0;
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
        $results = $server->taskCo($tasks, 27);
        foreach ($results as $result) {
            if ($result) {
                $RequestThrottle[$result['id']] = $result['result'];
            }
        }
        return $RequestThrottle;
    }
}