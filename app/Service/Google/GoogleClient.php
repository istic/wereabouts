<?php

namespace App\Service\Google;

use Google\Client;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
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
        return new Sheets($this->client);
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
     * @return array<int, Venue>
     */
    public function listVenues(): array
    {
        $cacheKey = 'venue.index.v3'; // Bump this suffix whenever the cached venue shape changes
        $staleCacheKey = 'venue.index.v3.stale';

        $cached = $this->readVenueCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            [$headings, $sites] = $this->getSheetData();
        } catch (RuntimeException $e) {
            Log::error('Failed to refresh venue list from Google Sheets, falling back to stale cache if available.', [
                'exception' => $e,
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
            $venues[] = Venue::fromSheetRow($headings, $site, $venue_open);
        }
        $venues = $this->sortVenuesByName($venues); // Sort venues by name
        $venues = $this->disambiguateSlugs($venues); // Ensure slugs are unique even if venue names collide

        $encoded = json_encode(array_map(fn (Venue $venue) => $venue->toArray(), $venues));
        if ($encoded === false) {
            Log::warning('Failed to encode venue list for caching, serving it without updating the cache.', [
                'json_error' => json_last_error_msg(),
            ]);

            return $venues;
        }

        Redis::setex($cacheKey, 3600, $encoded); // Cache for 1 hour
        Redis::set($staleCacheKey, $encoded); // Kept without expiry as a last-known-good fallback

        return $venues;
    }

    /**
     * @return array<int, Venue>|null
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

        try {
            return array_map(fn (array $venue) => Venue::fromArray($venue), $decoded);
        } catch (\Throwable $e) {
            Log::warning('Venue cache did not match the expected Venue shape, ignoring.', [
                'cache_key' => $cacheKey,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * @param  array<int, Venue>  $venues
     * @return array<int, Venue>
     */
    protected function disambiguateSlugs(array $venues): array
    {
        $slugCounts = [];

        return array_map(function (Venue $venue) use (&$slugCounts) {
            $slugCounts[$venue->slug] = ($slugCounts[$venue->slug] ?? 0) + 1;
            $count = $slugCounts[$venue->slug];

            return $count > 1 ? $venue->withSlug("{$venue->slug}-{$count}") : $venue;
        }, $venues);
    }

    public function findVenueBySlug(string $slug): ?Venue
    {
        foreach ($this->listVenues() as $venue) {
            if ($venue->slug === $slug) {
                return $venue;
            }
        }

        return null;
    }

    /**
     * @param  array<int, Venue>  $venues
     * @return array<int, Venue>
     */
    public function sortVenuesByName(array $venues): array
    {
        usort($venues, fn (Venue $a, Venue $b) => strcmp($a->name, $b->name));

        return $venues;
    }
}
