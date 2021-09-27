<?php

namespace Benchmark;

use Exception;

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

    public function checkListDomains($http, $domains)
    {
        dump($domains[0]);
//        return ['total_time_us' => null];
        foreach ($domains as $domain) {
            $site = ($domain['Scheme'] ?? 'https') . '://' . $domain['Host'] . '/';
            return Growth::checkDomain($site, $this->headers);
        }
    }

    /**
     * @param $site
     * @param $headers
     * @return array
     */
    public static function checkDomain($site, $headers): array
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

        if (curl_exec($curl) === false) {
//            dump(curl_error($curl));
            curl_close($curl);
            return $return;
        }

        $curl_info = curl_getinfo($curl);
//        dump($curl_info);s

        curl_close($curl);

        $return['http_code'] = (int)$curl_info['http_code'];
        if ($return['http_code'] != 200) {
            return $return;
        }

//        return $curl_info;
        $return['total_time_us'] = (int)$curl_info['total_time_us'];
        return $return;
    }
}