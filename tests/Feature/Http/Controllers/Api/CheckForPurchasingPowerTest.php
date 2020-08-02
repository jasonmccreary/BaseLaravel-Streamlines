<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Coupon;
use Carbon\Carbon;
use Facades\App\Services\Geocoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CheckForPurchasingPowerTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /**
     * @test
     */
    public function it_returns_a_400_when_no_ip_address_is_provided()
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => null,
        ])->get(route('api.purchasing-power'));

        $response->assertStatus(400);
    }

    /**
     * @test
     */
    public function it_returns_a_424_when_ip_address_can_not_be_geocoded()
    {
        $ip = $this->faker->ipv4;

        $geocoder = \Mockery::mock();
        $geocoder->shouldReceive('countryForIp')
            ->with($ip)
            ->andReturnNull();
        Geocoder::swap($geocoder);

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
        ])
            ->get(route('api.purchasing-power'));

        $response->assertStatus(424);
    }

    /**
     * @test
     */
    public function it_returns_a_200_when_ip_address_for_country_without_ppp()
    {
        $ip = $this->faker->ipv4;

        $geocoder = \Mockery::mock();
        $geocoder->shouldReceive('countryForIp')
            ->with($ip)
            ->andReturn('United States');
        Geocoder::swap($geocoder);

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
        ])
            ->get(route('api.purchasing-power'));

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_returns_banner_when_country_has_ppp()
    {
        $ip = $this->faker->ipv4;

        $coupon = factory(Coupon::class)->create([
            'code' => Coupon::CODE_55_OFF,
        ]);

        $geocoder = \Mockery::mock();
        $geocoder->shouldReceive('countryForIp')
            ->with($ip)
            ->andReturn('Egypt');
        Geocoder::swap($geocoder);

        $now = now();
        Carbon::setTestNow($now);

        $url = URL::temporarySignedRoute('purchasing-power', now()->addMinutes(3), ['code' => $coupon->code]);

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
        ])
            ->get(route('api.purchasing-power'));

        $response->assertStatus(200);
        $response->assertViewIs('partials.ppp-banner');
        $response->assertSee('action="'.$url.'"', false);
        $response->assertSee('Egypt');
        $response->assertSee($coupon->percent_off.'% discount');
    }
}
