<?php

namespace Tests\Unit;

use App\Service\Geocoding\NominatimGeocoder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class NominatimGeocoderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::flushdb();
    }

    public function test_it_geocodes_a_query_and_caches_the_result(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '53.4084', 'lon' => '-2.9916'],
            ]),
        ]);

        $geocoder = new NominatimGeocoder;

        $this->assertFalse($geocoder->isCached('Liverpool'));

        $coordinates = $geocoder->geocode('Liverpool');

        $this->assertSame(['lat' => 53.4084, 'lng' => -2.9916], $coordinates);
        $this->assertTrue($geocoder->isCached('Liverpool'));

        Http::assertSentCount(1);

        // A second lookup for the same query should be served from cache,
        // not issue a second request.
        $geocoder->geocode('Liverpool');
        Http::assertSentCount(1);
    }

    public function test_it_caches_a_negative_result_so_it_is_not_retried_every_request(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $geocoder = new NominatimGeocoder;

        $this->assertNull($geocoder->geocode('Nowhere in particular'));
        $this->assertTrue($geocoder->isCached('Nowhere in particular'));

        $this->assertNull($geocoder->geocode('Nowhere in particular'));
        Http::assertSentCount(1);
    }

    public function test_it_treats_a_failed_request_as_not_found_without_caching_it(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response(null, 500),
        ]);

        $geocoder = new NominatimGeocoder;

        $this->assertNull($geocoder->geocode('Somewhere'));
        $this->assertFalse($geocoder->isCached('Somewhere'));
    }
}
