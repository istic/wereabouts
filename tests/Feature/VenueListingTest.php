<?php

namespace Tests\Feature;

use App\Service\Google\GoogleClient;
use App\Service\Google\Venue;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class VenueListingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::del('venue.index.v2', 'venue.index.v2.stale');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_the_venue_page_returns_a_successful_response(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('Abney Scout and Guide Centre'),
        ]));

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_list_venues_skips_rows_with_no_venue_name(): void
    {
        $client = new FakeGoogleClient([
            $this->rawVenueRow(''),
            $this->rawVenueRow('Abney Scout and Guide Centre'),
        ]);

        $venues = $client->listVenues();

        $this->assertCount(1, $venues);
        $this->assertSame('Abney Scout and Guide Centre', $venues[0]->name);
    }

    public function test_list_venues_marks_venues_after_the_closed_sentinel_as_closed(): void
    {
        $client = new FakeGoogleClient([
            $this->rawVenueRow('Open Venue'),
            ['Closed Venues'],
            $this->rawVenueRow('Closed Venue'),
        ]);

        $venues = collect($client->listVenues());

        $this->assertTrue($venues->firstWhere('name', 'Open Venue')->open);
        $this->assertFalse($venues->firstWhere('name', 'Closed Venue')->open);
    }

    public function test_list_venues_handles_ragged_rows_with_missing_trailing_columns(): void
    {
        $client = new FakeGoogleClient([
            ['Short Row Venue'], // only the venue name column is present
        ]);

        $venues = $client->listVenues();

        $this->assertCount(1, $venues);
        $this->assertSame('Short Row Venue', $venues[0]->name);
        $this->assertNull($venues[0]->aspects);
    }

    public function test_list_venues_sanitizes_free_text_capacity_into_an_integer(): void
    {
        $client = new FakeGoogleClient([
            $this->rawVenueRow('Venue With Capacity', capacity: 'sleeps 32'),
        ]);

        $venues = $client->listVenues();

        $this->assertSame(32, $venues[0]->capacityCount);
    }

    public function test_list_venues_caches_results_and_serves_them_on_subsequent_calls(): void
    {
        $client = new FakeGoogleClient([
            $this->rawVenueRow('Cached Venue'),
        ]);

        $first = $client->listVenues();

        // A second call to a client whose fake sheet is now empty should still
        // return the previously cached venues, proving the cache was used.
        $client->rows = [];
        $second = $client->listVenues();

        $this->assertEquals($first, $second);
    }

    public function test_sort_venues_by_name_orders_venues_alphabetically(): void
    {
        $client = new FakeGoogleClient([]);

        $sorted = $client->sortVenuesByName([
            Venue::fromSheetRow(['Venue Name'], ['Zebra Hall'], true),
            Venue::fromSheetRow(['Venue Name'], ['Abney Centre'], true),
        ]);

        $this->assertSame(['Abney Centre', 'Zebra Hall'], array_map(fn (Venue $v) => $v->name, $sorted));
    }

    /**
     * @return array<int, string>
     */
    protected function rawVenueRow(string $name, string $capacity = 'sleeps 10'): array
    {
        return [
            $name,
            'Location',
            $capacity,
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

class FakeGoogleClient extends GoogleClient
{
    /**
     * @param  array<int, array<int, string>>  $rows
     */
    public function __construct(public array $rows = [])
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

        return [$headings, $this->rows];
    }
}
