<?php

namespace Tests\Feature;

use App\Service\Geocoding\NominatimGeocoder;
use App\Service\Google\GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\Concerns\InteractsWithMapPoints;
use Tests\TestCase;

class MapTest extends TestCase
{
    use InteractsWithMapPoints;

    protected function setUp(): void
    {
        parent::setUp();

        Redis::flushdb();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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

        $response = $this->get(route('map.index'));

        $response->assertStatus(200);
        $response->assertSee('id="venues-map"', false);

        $points = $this->pointsFromResponse($response);
        $this->assertCount(1, $points);
        $this->assertSame('Abney Scout and Guide Centre', $points[0]['name']);
        $this->assertSame(53.4084, $points[0]['lat']);
        $this->assertSame(-2.9916, $points[0]['lng']);
        $this->assertTrue($points[0]['open']);
        $this->assertSame(route('venue.show', 'abney-scout-and-guide-centre'), $points[0]['url']);
    }

    public function test_it_counts_venues_with_no_location_as_unmapped_without_geocoding_them(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('No Location Venue', ''),
        ]));

        Http::fake();

        $response = $this->get(route('map.index'));

        $response->assertStatus(200);
        $response->assertSee('1 venue could not be placed on the map automatically.');
        Http::assertNothingSent();
    }

    public function test_it_counts_a_venue_nominatim_could_not_resolve_as_unmapped(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('Unresolvable Venue', 'Nowhere in particular'),
        ]));

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $response = $this->get(route('map.index'));

        $response->assertStatus(200);
        $response->assertSee('1 venue could not be placed on the map automatically.');
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

        $response = $this->get(route('map.index'));

        $response->assertStatus(200);
        Http::assertSentCount(1);
        $response->assertSee('1 venue is still being located');
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
        Http::fake(); // Reset the request log so only the /map request's own calls count below.

        $response = $this->get(route('map.index'));

        $response->assertStatus(200);
        Http::assertNothingSent();
        $response->assertSee('Cached Venue');
        $response->assertDontSee('still being located');
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
