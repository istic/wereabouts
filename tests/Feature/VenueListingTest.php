<?php

namespace Tests\Feature;

use App\Service\Google\GoogleClient;
use App\Service\Google\Venue;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class VenueListingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::del(GoogleClient::venueCacheKey(), GoogleClient::venueCacheKey().'.stale');
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

    public function test_the_venue_page_renders_a_filter_bar_and_data_attributes_for_filtering(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('Abney Scout and Guide Centre', capacity: 'sleeps 32'),
            ['Closed Venues'],
            $this->rawVenueRow('Shuttered Hall'),
        ]));

        $response = $this->get('/');

        $response->assertStatus(200);

        // Filter controls for name, location, capacity, status, and the
        // Yes/No accessibility fields (issues #5, #6, #7).
        $response->assertSee('id="filter-name"', false);
        $response->assertSee('id="filter-location"', false);
        $response->assertSee('id="filter-capacity"', false);
        $response->assertSee('id="filter-status"', false);
        $response->assertSee('id="filter-public-transport"', false);
        $response->assertSee('id="filter-disabled-bathrooms"', false);
        $response->assertSee('id="filter-step-free"', false);

        // Each venue card carries the data attributes the client-side filter reads.
        $response->assertSee('data-name="abney scout and guide centre"', false);
        $response->assertSee('data-capacity="32"', false);
        $response->assertSee('data-open="1"', false);
        $response->assertSee('data-open="0"', false);
        $response->assertSee('data-public-transport="1"', false); // fixture's "Yes"
        $response->assertSee('data-disabled-bathrooms="0"', false); // fixture's "No"
        $response->assertSee('data-step-free="1"', false); // fixture's "All spaces"

        // Each venue is visibly badged with its status, scoped to its own card
        // by asserting the badge markup itself appears after the venue's name.
        // The badge id is suffixed with the venue's slug since this partial is
        // included once per card, and ids must stay unique across the page.
        $response->assertSeeInOrder(['Abney Scout and Guide Centre', 'id="Venue_Status_abney-scout-and-guide-centre" class="badge bg-success">Open'], false);
        $response->assertSeeInOrder(['Shuttered Hall', 'id="Venue_Status_shuttered-hall" class="badge bg-secondary">Closed'], false);
    }

    public function test_the_venue_page_lowercases_multibyte_venue_names_for_filtering(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('Café Union', location: 'Dún Laoghaire'),
        ]));

        $response = $this->get('/');

        // Str::lower() is multibyte-aware (unlike strtolower()), so the
        // data-name/data-location attributes the JS filter matches against
        // must lowercase accented characters correctly.
        $response->assertSee('data-name="café union"', false);
        $response->assertSee('data-location="dún laoghaire"', false);
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

    public function test_list_venues_refetches_when_the_cache_is_in_an_old_incompatible_shape(): void
    {
        // Simulates a cache entry written by a previous deploy that cached
        // venues as raw sheet-keyed arrays instead of Venue::toArray().
        Redis::set(GoogleClient::venueCacheKey(), json_encode([
            ['Venue Name' => 'Stale Shape Venue', 'data' => ['open' => true]],
        ]));

        $client = new FakeGoogleClient([
            $this->rawVenueRow('Fresh Venue'),
        ]);

        $venues = $client->listVenues();

        $this->assertCount(1, $venues);
        $this->assertSame('Fresh Venue', $venues[0]->name);
    }

    public function test_list_venues_exposes_a_url_venue_name_as_its_website(): void
    {
        $client = new FakeGoogleClient([
            $this->rawVenueRow('https://example.com/venue'),
            $this->rawVenueRow('www.example.org'),
            $this->rawVenueRow('Ordinary Venue Name'),
        ]);

        $venues = collect($client->listVenues());

        $this->assertSame('https://example.com/venue', $venues->firstWhere('name', 'https://example.com/venue')->website);
        $this->assertSame('http://www.example.org', $venues->firstWhere('name', 'www.example.org')->website);
        $this->assertNull($venues->firstWhere('name', 'Ordinary Venue Name')->website);
    }

    public function test_list_venues_exposes_a_hyperlinked_venue_name_as_its_website(): void
    {
        // The common real-world case: the sheet shows the venue's actual name,
        // but the cell text is linked to the venue's website, rather than the
        // cell literally containing the URL as text.
        $client = new FakeGoogleClient([
            $this->rawVenueRow('Moor House Adventure Centre'),
            $this->rawVenueRow('Ordinary Venue Name'),
        ], nameHyperlinks: [
            'https://moor-house.org.uk/',
            null,
        ]);

        $venues = collect($client->listVenues());

        $this->assertSame('https://moor-house.org.uk/', $venues->firstWhere('name', 'Moor House Adventure Centre')->website);
        $this->assertNull($venues->firstWhere('name', 'Ordinary Venue Name')->website);
    }

    public function test_list_venues_ignores_a_non_http_hyperlink_on_the_venue_name(): void
    {
        // A stray mailto:/tel: link (or an empty hyperlink string) shouldn't
        // be trusted as a website just because the cell has some hyperlink.
        $client = new FakeGoogleClient([
            $this->rawVenueRow('Venue With A Mailto Link'),
            $this->rawVenueRow('Venue With An Empty Hyperlink'),
        ], nameHyperlinks: [
            'mailto:someone@example.com',
            '',
        ]);

        $venues = collect($client->listVenues());

        $this->assertNull($venues->firstWhere('name', 'Venue With A Mailto Link')->website);
        $this->assertNull($venues->firstWhere('name', 'Venue With An Empty Hyperlink')->website);
    }

    public function test_venue_cache_key_is_derived_from_the_configured_version(): void
    {
        config(['app.venue_cache_version' => 'test-marker']);

        $this->assertSame('venue.index.vtest-marker', GoogleClient::venueCacheKey());
    }

    public function test_the_venue_details_render_a_website_link_when_the_name_is_a_url(): void
    {
        app()->bind(GoogleClient::class, fn () => new FakeGoogleClient([
            $this->rawVenueRow('https://example.com/venue'),
        ]));

        $response = $this->get('/');

        $response->assertSee('Website', false);
        $response->assertSee('href="https://example.com/venue"', false);
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
    protected function rawVenueRow(string $name, string $capacity = 'sleeps 10', string $location = 'Location'): array
    {
        return [
            $name,
            $location,
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
