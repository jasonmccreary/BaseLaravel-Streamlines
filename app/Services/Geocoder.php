<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Geocoder
{
    public function countryForIp($ip)
    {
        $response = Http::timeout(1)->get('https://freegeoip.app/json/' . $ip);

        if ($response->failed() || empty($response['country_name'])) {
            return;
        }

        return $response['country_name'];
    }
}
