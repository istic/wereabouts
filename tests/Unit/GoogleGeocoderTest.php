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

    public function test_it_restricts_the_query_to_the_configured_country(): void
    {
        config(['services.google_maps.key' => 'test-key', 'app.geocode_country_code' => 'gb']);

        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [
                    ['geometry' => ['location' => ['lat' => 53.0, 'lng' => -1.5]]],
                ],
            ]),
        ]);

        (new GoogleGeocoder)->geocode('White Hall, Derbyshire');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'components=country%3AGB'));
    }

    public function test_the_country_restriction_can_be_disabled(): void
    {
        config(['services.google_maps.key' => 'test-key', 'app.geocode_country_code' => '']);

        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'OK', 'results' => [
                ['geometry' => ['location' => ['lat' => 53.0, 'lng' => -1.5]]],
            ]]),
        ]);

        (new GoogleGeocoder)->geocode('Liverpool');

        Http::assertSent(fn ($request) => ! str_contains($request->url(), 'components'));
    }

    public function test_a_result_cached_under_one_country_restriction_is_not_reused_under_another(): void
    {
        config(['services.google_maps.key' => 'test-key']);

        // Http::fake() checks the earliest-registered matching stub first,
        // so both responses need registering up front as a sequence
        // rather than via a second Http::fake() call later.
        Http::fake([
            'maps.googleapis.com/*' => Http::sequence()
                ->push([
                    'status' => 'OK',
                    'results' => [
                        ['geometry' => ['location' => ['lat' => 53.2843, 'lng' => -1.9531]]],
                    ],
                ])
                ->push([
                    'status' => 'OK',
                    'results' => [
                        ['geometry' => ['location' => ['lat' => 38.6799, 'lng' => -76.9878]]],
                    ],
                ]),
        ]);

        config(['app.geocode_country_code' => 'gb']);
        (new GoogleGeocoder)->geocode('White Hall, Derbyshire');

        // Switching the restriction (e.g. disabling it, or to another
        // country) must not reuse the GB-restricted result.
        config(['app.geocode_country_code' => '']);
        $this->assertFalse((new GoogleGeocoder)->isCached('White Hall, Derbyshire'));

        $unrestricted = (new GoogleGeocoder)->geocode('White Hall, Derbyshire');

        $this->assertSame(['lat' => 38.6799, 'lng' => -76.9878], $unrestricted);
    }
}
