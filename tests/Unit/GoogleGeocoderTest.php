<?php

namespace Tests\Unit;

use App\Service\Geocoding\GoogleGeocoder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class GoogleGeocoderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::flushdb();
    }

    public function test_it_is_inert_without_an_api_key_configured(): void
    {
        config(['services.google_maps.key' => null]);

        Http::fake();

        $geocoder = new GoogleGeocoder;

        $this->assertFalse($geocoder->isConfigured());
        $this->assertNull($geocoder->geocode('Liverpool'));
        Http::assertNothingSent();
    }

    public function test_it_geocodes_a_query_and_caches_the_result(): void
    {
        config(['services.google_maps.key' => 'test-key']);

        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [
                    ['geometry' => ['location' => ['lat' => 51.5074, 'lng' => -0.1278]]],
                ],
            ]),
        ]);

        $geocoder = new GoogleGeocoder;

        $this->assertFalse($geocoder->isCached('Liverpool'));

        $coordinates = $geocoder->geocode('Liverpool');

        $this->assertSame(['lat' => 51.5074, 'lng' => -0.1278], $coordinates);
        $this->assertTrue($geocoder->isCached('Liverpool'));

        Http::assertSentCount(1);

        $geocoder->geocode('Liverpool');
        Http::assertSentCount(1);
    }

    public function test_it_caches_a_zero_results_response_so_it_is_not_retried_every_request(): void
    {
        config(['services.google_maps.key' => 'test-key']);

        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'ZERO_RESULTS', 'results' => []]),
        ]);

        $geocoder = new GoogleGeocoder;

        $this->assertNull($geocoder->geocode('Nowhere in particular'));
        $this->assertTrue($geocoder->isCached('Nowhere in particular'));

        $this->assertNull($geocoder->geocode('Nowhere in particular'));
        Http::assertSentCount(1);
    }

    public function test_it_treats_a_failed_request_as_not_found_without_caching_it(): void
    {
        config(['services.google_maps.key' => 'test-key']);

        Http::fake([
            'maps.googleapis.com/*' => Http::response(null, 500),
        ]);

        $geocoder = new GoogleGeocoder;

        $this->assertNull($geocoder->geocode('Somewhere'));
        $this->assertFalse($geocoder->isCached('Somewhere'));
    }

    public function test_it_treats_a_non_ok_api_status_as_not_found_without_caching_it(): void
    {
        config(['services.google_maps.key' => 'test-key']);

        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'OVER_QUERY_LIMIT', 'results' => []]),
        ]);

        $geocoder = new GoogleGeocoder;

        $this->assertNull($geocoder->geocode('Somewhere'));
        $this->assertFalse($geocoder->isCached('Somewhere'));
    }
}
