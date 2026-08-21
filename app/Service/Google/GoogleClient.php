<?php

namespace App\Service\Google;

use Google\Client;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleClient
{
    protected Client $client;

    protected string $sheetID;

    public function __construct()
    {
        $this->client = new Client;
        $this->client->setApplicationName(config('app.name'));
        $this->client->setScopes([
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/spreadsheets',
        ]);
        $this->client->setAuthConfig(storage_path('app/credentials/'.config('app.google_credentials_filename')));
        $this->client->setAccessType('offline');

        $this->sheetID = config('app.venue_sheet_id');
    }

    protected function getSheet(): Sheets
    {

        $service = new Sheets($this->client);

        return $service;
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<int, string>>}
     */
    protected function getSheetData(): array
    {

        $sheet = $this->getSheet();

        $range = 'Overnight Venue Site List!A:M'; // Adjust the range as needed

        try {
            $result = $sheet->spreadsheets_values->get($this->sheetID, $range);
        } catch (GoogleServiceException $e) {
            throw new RuntimeException("Failed to fetch venue sheet '{$this->sheetID}' (range {$range}): {$e->getMessage()}", previous: $e);
        }

        $sites = $result->getValues() ?? [];
        if (count($sites) < 2) {
            throw new RuntimeException("Venue sheet '{$this->sheetID}' returned fewer than 2 rows (expected a heading row and a spacer row); the sheet layout may have changed.");
        }

        $headings = array_shift($sites); // Remove the first row as headings
        $headings[0] = 'Venue Name'; // Rename the first heading to 'Venue Name'
        array_shift($sites); // Remove the second row as it is not needed

        return [$headings, $sites];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listVenues(): array
    {
        $cacheKey = 'venue.index.v2'; // Bump this suffix whenever the cached venue shape changes
        $staleCacheKey = 'venue.index.v2.stale';

        $cached = $this->readVenueCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            [$headings, $sites] = $this->getSheetData();
        } catch (RuntimeException $e) {
            Log::error('Failed to refresh venue list from Google Sheets, falling back to stale cache if available.', [
                'exception' => $e->getMessage(),
            ]);

            $stale = $this->readVenueCache($staleCacheKey);
            if ($stale !== null) {
                return $stale;
            }

            throw $e;
        }

        $venues = [];
        $venue_open = true;
        foreach ($sites as $site) {
            if (count($site) < 1 || ! $site[0]) {
                continue; // Skip if the first column (Venue Name) is empty
            }
            $site = array_map('trim', $site); // Trim whitespace from all columns
            if ($site[0] === 'Closed Venues') {
                $venue_open = false; // If the first column is 'Closed', set open status to false

                continue; // Skip this row as it indicates closed venues
            }
            $venue = [];
            foreach ($headings as $index => $heading) {
                $venue[$heading] = isset($site[$index]) ? $site[$index] : null;
            }
            $venue['data'] = []; // Initialize 'data' as an empty array
            $venue['data']['open'] = $venue_open; // Add 'open' status to each venue
            $venue['data']['capacity_count'] = (int) filter_var($venue['Capacity'] ?? '', FILTER_SANITIZE_NUMBER_INT); // Ensure capacity count is an integer
            $venue['data']['public_transport_guess'] = filter_var($venue['Public Transport'] ?? '', FILTER_VALIDATE_BOOL); // Convert to boolean
            $venue['data']['disabled_bathrooms_guess'] = filter_var($venue['Disabled bathrooms?'] ?? '', FILTER_VALIDATE_BOOL); // Convert to boolean
            $venue['data']['slug'] = Str::slug($venue['Venue Name']); // Slug used to link to the venue's own page
            $venues[] = $venue;
        }
        $venues = $this->sortVenuesByName($venues); // Sort venues by name
        $venues = $this->disambiguateSlugs($venues); // Ensure slugs are unique even if venue names collide

        $encoded = json_encode($venues);
        Redis::setex($cacheKey, 3600, $encoded); // Cache for 1 hour
        Redis::set($staleCacheKey, $encoded); // Kept without expiry as a last-known-good fallback

        return $venues;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    protected function readVenueCache(string $cacheKey): ?array
    {
        $raw = Redis::get($cacheKey);
        if ($raw === null || $raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            Log::warning('Venue cache contained invalid JSON, ignoring.', [
                'cache_key' => $cacheKey,
                'json_error' => json_last_error_msg(),
            ]);

            return null;
        }

        return $decoded;
    }

    /**
     * @param  array<int, array<string, mixed>>  $venues
     * @return array<int, array<string, mixed>>
     */
    protected function disambiguateSlugs(array $venues): array
    {
        $slugCounts = [];
        foreach ($venues as &$venue) {
            $slug = $venue['data']['slug'];
            $slugCounts[$slug] = ($slugCounts[$slug] ?? 0) + 1;
            if ($slugCounts[$slug] > 1) {
                $venue['data']['slug'] = $slug.'-'.$slugCounts[$slug];
            }
        }

        return $venues;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findVenueBySlug(string $slug): ?array
    {
        foreach ($this->listVenues() as $venue) {
            if ($venue['data']['slug'] === $slug) {
                return $venue;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $venues
     * @return array<int, array<string, mixed>>
     */
    public function sortVenuesByName(array $venues): array
    {
        usort($venues, function ($a, $b) {
            return strcmp($a['Venue Name'], $b['Venue Name']);
        });

        return $venues;
    }
}
