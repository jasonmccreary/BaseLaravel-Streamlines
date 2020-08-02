<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    const CODE_20_OFF = '...';
    const CODE_30_OFF = '...';
    const CODE_45_OFF = '...';
    const CODE_55_OFF = '0123456789';

    protected $casts = [
        'id' => 'integer',
    ];

    protected $dates = [
        'expired_at',
    ];

    public function wasAlreadyUsed(User $user = null)
    {
        if (! $user) {
            return false;
        }

        return \App\Order::where('user_id', $user->id)->where('coupon_id', $this->id)->exists();
    }

    public static function findByCountry($country)
    {
        $factor = PurchasingPower::factorForCountry($country);
        if (is_null($factor)) {
            return;
        }

        $code = Coupon::CODE_20_OFF;
        if ($factor < .3) {
            $code = Coupon::CODE_55_OFF;
        } elseif ($factor < .45) {
            $code = Coupon::CODE_45_OFF;
        } elseif ($factor < .6) {
            $code = Coupon::CODE_30_OFF;
        }

        return self::where('code', $code)->first();
    }

    // ...
}
