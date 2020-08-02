<?php

namespace App;

class PurchasingPower
{
    private static $factors = [
        // ...
        'Egypt' => 0.2073601971303492,
        // ...
    ];

    public static function factorForCountry($country)
    {
        return self::$factors[$country] ?? null;
    }
}
