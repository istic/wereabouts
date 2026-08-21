<?php

namespace Tests\Feature;

use App\Service\Google\GoogleClient;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class VenueSlugCollisionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Redis::del('venue.index.v4', 'venue.index.v4.stale');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_venues_with_the_same_name_get_unique_slugs(): void
    {
        $client = new FakeCollidingGoogleClient;

        $venues = $client->listVenues();

        $slugs = array_map(fn ($venue) => $venue->slug, $venues);

        $this->assertSame(['abney-hall', 'abney-hall-2'], $slugs);
        $this->assertNotNull($client->findVenueBySlug('abney-hall'));
        $this->assertNotNull($client->findVenueBySlug('abney-hall-2'));
    }
}

class FakeCollidingGoogleClient extends GoogleClient
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

        $row = fn (string $name, string $location) => [
            $name, $location, 'sleeps 10', 'Hall', 'Yes', 'All spaces', 'No', 'Good', '', '', '', '', '',
        ];

        $sites = [
            $row('Abney Hall', 'Cheadle'),
            $row('Abney Hall', 'Elsewhere'),
        ];

        return [$headings, $sites];
    }
}
