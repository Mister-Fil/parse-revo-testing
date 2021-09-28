<?php

namespace Benchmark;

use Swoole\Server;
use Swoole\Http\Server as HttpServer;

class Growth
{

    /**
     * @var array|bool[]
     */
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
     * @param HttpServer $server
     * @param $domains
     * @param $headers
     * @return array
     */
    public function checkDomainMultiple(HttpServer $server, $domains, $headers): array
    {
        $tasks = [];
        foreach ($domains as $domain) {
            $site = ($domain['Scheme'] ?? 'https') . '://' . $domain['Host'] . '/';
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
        $results = $server->taskCo($tasks, 27);
        foreach ($results as $result) {
            if ($result) {
                $this->RequestThrottle[$result['id']] = $result['result'];
            }
        }
        return $this->RequestThrottle;
    }

    /**
     * @param Server $server
     * @param $site
     * @param $headers
     * @return int
     */
    public static function checkDomain(Server $server, $site, $headers): int
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
     * @param $resultTasks
     * @return bool
     */
    public static function checkResponses($resultTasks): bool
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
}