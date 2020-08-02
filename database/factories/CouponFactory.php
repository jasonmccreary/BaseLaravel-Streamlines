<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use Faker\Generator as Faker;

$factory->define(\App\Coupon::class, function (Faker $faker) {
    return [
        'code' => $faker->md5,
        'percent_off' => $faker->numberBetween(10, 70),
        'expired_at' => null,
    ];
});

$factory->state(\App\Coupon::class, 'expired', [
    'expired_at' => now(),
]);
