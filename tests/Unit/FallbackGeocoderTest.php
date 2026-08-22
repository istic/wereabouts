<?php

namespace Tests\Unit;

use App\Service\Geocoding\FallbackGeocoder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class FallbackGeocoderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::flushdb();
    }

    public function test_it_uses_nominatim_when_it_resolves_the_query(): void
    {
        config(['services.google_maps.key' => 'test-key']);

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '53.4084', 'lon' => '-2.9916'],
            ]),
        ]);

        $geocoder = app(FallbackGeocoder::class);

        $this->assertSame(['lat' => 53.4084, 'lng' => -2.9916], $geocoder->geocode('Liverpool'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'googleapis.com'));
    }

    public function test_it_falls_back_to_google_when_nominatim_finds_nothing_and_google_is_configured(): void
    {
        config(['services.google_maps.key' => 'test-key']);

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [
                    ['geometry' => ['location' => ['lat' => 51.5074, 'lng' => -0.1278]]],
                ],
            ]),
        ]);

        $geocoder = app(FallbackGeocoder::class);

        $this->assertSame(['lat' => 51.5074, 'lng' => -0.1278], $geocoder->geocode('Somewhere Nominatim Misses'));
    }

    public function test_it_stays_null_when_nominatim_fails_and_google_is_not_configured(): void
    {
        config(['services.google_maps.key' => null]);

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $geocoder = app(FallbackGeocoder::class);

        $this->assertNull($geocoder->geocode('Somewhere Nominatim Misses'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'googleapis.com'));
    }

    public function test_is_cached_reflects_only_nominatims_cache_state(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $geocoder = app(FallbackGeocoder::class);

        $this->assertFalse($geocoder->isCached('Somewhere Nominatim Misses'));

        $geocoder->geocode('Somewhere Nominatim Misses');

        // Nominatim has now given up on this query (and cached that), even
        // though the point may still be unresolved overall via Google.
        $this->assertTrue($geocoder->isCached('Somewhere Nominatim Misses'));
    }
}
