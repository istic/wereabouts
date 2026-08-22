<?php

namespace Tests\Feature;

use App\Service\Google\GoogleClient;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class VenueShowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::flushdb();
        config(['services.google_maps.key' => null]);

        app()->bind(GoogleClient::class, function () {
            return new FakeVenueGoogleClient;
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_venue_page_is_linked_from_the_index(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('venue.show', 'abney-scout-and-guide-centre'), false);
    }

    public function test_venue_page_shows_venue_details_without_geocoding_anything(): void
    {
        Http::fake();

        $response = $this->get(route('venue.show', 'abney-scout-and-guide-centre'));

        $response->assertStatus(200);
        $response->assertSee('Abney Scout and Guide Centre');
        $response->assertSee('Cheadle, nr Stockport');
        Http::assertNothingSent();
    }

    public function test_unknown_venue_slug_returns_404(): void
    {
        $response = $this->get(route('venue.show', 'not-a-real-venue'));

        $response->assertStatus(404);
    }

    public function test_venue_page_includes_a_map_pointing_at_its_points_endpoint(): void
    {
        Http::fake();

        $response = $this->get(route('venue.show', 'abney-scout-and-guide-centre'));

        $response->assertStatus(200);
        $response->assertSee('id="venues-map"', false);
        $response->assertSee('data-points-url="'.route('venue.points', 'abney-scout-and-guide-centre').'"', false);
        Http::assertNothingSent();
    }

    public function test_venue_points_endpoint_returns_the_geocoded_point(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '53.4084', 'lon' => '-2.9916'],
            ]),
        ]);

        $response = $this->get(route('venue.points', 'abney-scout-and-guide-centre'));

        $response->assertStatus(200);
        $points = $response->json('points');
        $this->assertCount(1, $points);
        $this->assertSame('Abney Scout and Guide Centre', $points[0]['name']);
        $this->assertSame(53.4084, $points[0]['lat']);
        $this->assertSame(-2.9916, $points[0]['lng']);
        $response->assertJson(['unmapped' => [], 'pending' => 0]);
    }

    public function test_venue_points_endpoint_reports_when_it_could_not_be_placed_on_the_map(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $response = $this->get(route('venue.points', 'abney-scout-and-guide-centre'));

        $response->assertStatus(200);
        $response->assertExactJson(['points' => [], 'unmapped' => [[
            'name' => 'Abney Scout and Guide Centre',
            'url' => route('venue.show', 'abney-scout-and-guide-centre'),
            'reason' => 'could not be located',
        ]], 'pending' => 0]);
    }

    public function test_venue_points_endpoint_falls_back_to_google_when_nominatim_cannot_resolve_it(): void
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

        $response = $this->get(route('venue.points', 'abney-scout-and-guide-centre'));

        $response->assertStatus(200);
        $points = $response->json('points');
        $this->assertCount(1, $points);
        $this->assertSame(51.5074, $points[0]['lat']);
        $this->assertSame(-0.1278, $points[0]['lng']);
    }

    public function test_unknown_venue_slug_returns_404_from_the_points_endpoint(): void
    {
        $response = $this->get(route('venue.points', 'not-a-real-venue'));

        $response->assertStatus(404);
    }
}

class VenueShowMissingColumnsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::flushdb();

        app()->bind(GoogleClient::class, function () {
            return new FakeShortRowGoogleClient;
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_venue_page_renders_when_trailing_columns_are_missing(): void
    {
        $response = $this->get(route('venue.show', 'bare-bones-venue'));

        $response->assertStatus(200);
        $response->assertSee('Bare Bones Venue');
    }
}

class FakeShortRowGoogleClient extends GoogleClient
{
    public function __construct()
    {
        $this->sheetID = 'fake_sheet_id';
    }

    protected function getSheet(): Sheets
    {
        return Mockery::mock(Sheets::class);
    }

    protected function getSheetData(): array
    {
        $headings = [
            'Venue Name',
            'Location',
            'Capacity',
            'Types of Spaces',
            'Public Transport',
            'Step free access',
            'Disabled bathrooms?',
            'Internet?',
            'Kitchen',
            'Issues',
            'Further description of indoor spaces',
            'Aspects',
            'Price data (cost + data of recorded cost)',
        ];

        // A row shorter than $headings, as happens when trailing sheet
        // columns (like Aspects) are left blank, leaving those fields null.
        $sites = [
            ['Bare Bones Venue', 'Nowhere'],
        ];

        return [$headings, $sites, [null]];
    }
}

class FakeVenueGoogleClient extends GoogleClient
{
    public function __construct()
    {
        $this->sheetID = 'fake_sheet_id';
    }

    protected function getSheet(): Sheets
    {
        return Mockery::mock(Sheets::class);
    }

    protected function getSheetData(): array
    {
        $data = [
            [
                'Venue Name' => 'Abney Scout and Guide Centre',
                'Location' => 'Cheadle, nr Stockport',
                'Capacity' => 'sleeps 32',
                'Types of Spaces' => 'Indoor bunks, hall, firepit',
                'Public Transport' => 'Yes',
                'Step free access' => 'All spaces',
                'Disabled bathrooms?' => 'No',
                'Internet?' => 'Good mobile data coverage',
                'Kitchen' => '',
                'Issues' => 'No mould but the bedrooms are super dusty and the air quality is v low.',
                'Further description of indoor spaces' => '',
                'Aspects' => '',
                'Price data (cost + data of recorded cost)' => '£570 for two nights, extra £50 for someone to clean it',
            ],
        ];

        return [array_keys($data[0]), [array_values($data[0])], [null]];
    }
}
