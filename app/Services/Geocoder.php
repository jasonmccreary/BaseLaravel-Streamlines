<?php

namespace App\Services;

use GuzzleHttp\Client;

class Geocoder
{
    public function countryForIp($ip)
    {
        $client = new Client([
            'base_uri' => 'https://freegeoip.app',
            'timeout' => 1.0,
        ]);

        $response = $client->request('GET', 'json/'.$ip);
        $data = json_decode($response->getBody(), true);

        if ($data === false || empty($data['country_name'])) {
            return;
        }

        return $data['country_name'];
    }
}