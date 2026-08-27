<?php

namespace Tests\Feature;

use App\Service\Geocoding\NominatimGeocoder;
use App\Service\Google\GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class MapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::flushdb();
        config(['services.google_maps.key' => null]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_the_map_page_renders_without_geocoding_anything(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('Abney Scout and Guide Centre', 'Cheadle, nr Stockport'),
        ]));

        Http::fake();

        $response = $this->get(route('map.index'));

        $response->assertStatus(200);
        $response->assertSee('id="venues-map"', false);
        $response->assertSee('data-points-url="'.route('map.points').'"', false);
        Http::assertNothingSent();
    }

    public function test_the_map_page_renders_the_same_filter_bar_as_the_venue_listing(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([]));

        Http::fake();

        $response = $this->get(route('map.index'));

        $response->assertStatus(200);
        $response->assertSee('id="filter-name"', false);
        $response->assertSee('id="filter-location"', false);
        $response->assertSee('id="filter-capacity"', false);
        $response->assertSee('id="filter-status"', false);
        $response->assertSee('id="filter-public-transport"', false);
        $response->assertSee('id="filter-disabled-bathrooms"', false);
        $response->assertSee('id="filter-step-free"', false);
        $response->assertSee('id="filter-reset"', false);
    }

    public function test_it_plots_geocodable_venues_on_the_map(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('Abney Scout and Guide Centre', 'Cheadle, nr Stockport'),
        ]));

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '53.4084', 'lon' => '-2.9916'],
            ]),
        ]);

        $response = $this->get(route('map.points'));

        $response->assertStatus(200);
        $points = $response->json('points');
        $this->assertCount(1, $points);
        $this->assertSame('Abney Scout and Guide Centre', $points[0]['name']);
        $this->assertSame(53.4084, $points[0]['lat']);
        $this->assertSame(-2.9916, $points[0]['lng']);
        $this->assertTrue($points[0]['open']);
        $this->assertSame(route('venue.show', 'abney-scout-and-guide-centre'), $points[0]['url']);
        $response->assertJson(['unmapped' => [], 'pending' => 0]);
    }

    public function test_each_point_includes_the_same_fields_the_venue_listing_filters_use(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('Abney Scout and Guide Centre', 'Cheadle, nr Stockport'),
        ]));

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '53.4084', 'lon' => '-2.9916'],
            ]),
        ]);

        $response = $this->get(route('map.points'));

        $response->assertStatus(200);
        $points = $response->json('points');
        $this->assertSame('Cheadle, nr Stockport', $points[0]['location']);
        $this->assertSame(10, $points[0]['capacity']); // fixture's "sleeps 10"
        $this->assertTrue($points[0]['publicTransport']); // fixture's "Yes"
        $this->assertFalse($points[0]['disabledBathrooms']); // fixture's "No"
        $this->assertTrue($points[0]['stepFree']); // fixture's "All spaces"
    }

    public function test_it_lists_venues_with_no_location_as_unmapped_without_geocoding_them(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('No Location Venue', ''),
        ]));

        Http::fake();

        $response = $this->get(route('map.points'));

        $response->assertStatus(200);
        $response->assertExactJson(['points' => [], 'unmapped' => [[
            'name' => 'No Location Venue',
            'url' => route('venue.show', 'no-location-venue'),
            'reason' => 'no location listed',
        ]], 'pending' => 0]);
        Http::assertNothingSent();
    }

    public function test_it_lists_a_venue_neither_geocoder_could_resolve_as_unmapped(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('Unresolvable Venue', 'Nowhere in particular'),
        ]));

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $response = $this->get(route('map.points'));

        $response->assertStatus(200);
        $response->assertExactJson(['points' => [], 'unmapped' => [[
            'name' => 'Unresolvable Venue',
            'url' => route('venue.show', 'unresolvable-venue'),
            'reason' => 'could not be located',
        ]], 'pending' => 0]);
    }

    public function test_it_falls_back_to_google_when_nominatim_cannot_resolve_a_venue(): void
    {
        config(['services.google_maps.key' => 'test-key']);

        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('Google Only Venue', 'Somewhere Nominatim Misses'),
        ]));

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [
                    ['geometry' => ['location' => ['lat' => 51.5074, 'lng' => -0.1278]]],
                ],
            ]),
        ]);

        $response = $this->get(route('map.points'));

        $response->assertStatus(200);
        $points = $response->json('points');
        $this->assertCount(1, $points);
        $this->assertSame('Google Only Venue', $points[0]['name']);
        $this->assertSame(51.5074, $points[0]['lat']);
        $this->assertSame(-0.1278, $points[0]['lng']);
        $response->assertJson(['unmapped' => []]);
    }

    public function test_it_caps_live_lookups_per_request_and_reports_the_rest_as_pending(): void
    {
        config(['app.map_max_live_geocodes_per_request' => 1]);

        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('First Venue', 'Somewhere'),
            $this->rawVenueRow('Second Venue', 'Somewhere Else'),
        ]));

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '53.4084', 'lon' => '-2.9916'],
            ]),
        ]);

        $response = $this->get(route('map.points'));

        $response->assertStatus(200);
        Http::assertSentCount(1);
        $response->assertJson(['pending' => 1]);
    }

    public function test_an_already_cached_venue_does_not_count_against_the_live_lookup_cap(): void
    {
        config(['app.map_max_live_geocodes_per_request' => 0]);

        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('Cached Venue', 'Somewhere'),
        ]));

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '53.4084', 'lon' => '-2.9916'],
            ]),
        ]);

        (new NominatimGeocoder)->geocode('Cached Venue, Somewhere');
        Http::fake(); // Reset the request log so only the request below counts.

        $response = $this->get(route('map.points'));

        $response->assertStatus(200);
        Http::assertNothingSent();
        $response->assertJson(['pending' => 0]);
        $this->assertCount(1, $response->json('points'));
    }

    protected function rawVenueRow(string $name, string $location): array
    {
        return [
            $name,
            $location,
            'sleeps 10',
            'Types of Spaces',
            'Yes',
            'All spaces',
            'No',
            'Internet coverage',
            'Kitchen',
            'Issues',
            'Description',
            'Aspects',
            'Price data',
        ];
    }
}
