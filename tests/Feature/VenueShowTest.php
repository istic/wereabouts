<?php

namespace Tests\Feature;

use App\Service\Google\GoogleClient;
use Google\Service\Sheets;
use Mockery;
use Tests\TestCase;

class VenueShowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_venue_page_shows_venue_details(): void
    {
        $response = $this->get(route('venue.show', 'abney-scout-and-guide-centre'));

        $response->assertStatus(200);
        $response->assertSee('Abney Scout and Guide Centre');
        $response->assertSee('Cheadle, nr Stockport');
    }

    public function test_unknown_venue_slug_returns_404(): void
    {
        $response = $this->get(route('venue.show', 'not-a-real-venue'));

        $response->assertStatus(404);
    }
}

class VenueShowMissingColumnsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
