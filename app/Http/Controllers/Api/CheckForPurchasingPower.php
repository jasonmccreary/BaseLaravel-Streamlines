<?php

namespace App\Http\Controllers\Api;

use App\Coupon;
use App\Http\Controllers\Controller;
use App\Services\Geocoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class CheckForPurchasingPower extends Controller
{
    public function __invoke(Request $request)
    {
        if (empty($request->server('REMOTE_ADDR'))) {
            abort(400);
        }

        $geocoder = new Geocoder();
        $country = $geocoder->countryForIp($request->server('REMOTE_ADDR'));
        if (is_null($country)) {
            abort(424);
        }

        $coupon = Coupon::findByCountry($country);
        if (is_null($coupon)) {
            return response()->noContent(200);
        }

        return view('partials.ppp-banner', [
            'country' => $country,
            'url' => URL::temporarySignedRoute('purchasing-power', now()->addMinutes(3), ['code' => $coupon->code]),
            'percent_off' => $coupon->percent_off,
        ]);
    }
}
