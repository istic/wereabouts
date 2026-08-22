<?php

namespace App\Service\Geocoding;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Turns a free-text location into coordinates via the Google Maps Platform
 * Geocoding API, used as a fallback when Nominatim (OpenStreetMap's free
 * geocoder, tried first) can't resolve a query - its data and address
 * matching are noticeably less reliable for some locations.
 *
 * This needs a Google Maps Platform API key (services.google_maps.key) -
 * a different credential from the service account used for the Sheets
 * integration, since the Geocoding API doesn't accept that credential
 * type. With no key configured, this geocoder is inert and always
 * returns null, so the fallback is simply skipped.
 */
class GoogleGeocoder
{
    protected const ENDPOINT = 'https://maps.googleapis.com/maps/api/geocode/json';

    /**
     * Sentinel cached value for a query Google couldn't resolve, so a
     * bad/unresolvable address isn't retried on every single request.
     */
    protected const NOT_FOUND = '__not_found__';

    protected const FOUND_TTL = 60 * 60 * 24 * 30; // 30 days

    protected const NOT_FOUND_TTL = 60 * 60 * 24; // 1 day, in case it was transient

    /**
     * The Redis key a query's coordinates are cached under, versioned so
     * bumping it after a shape change only requires touching one place.
     * Kept separate from NominatimGeocoder's cache key, since the two
     * geocoders are different data sources that can disagree.
     */
    public static function cacheKey(string $query): string
    {
        return 'geocode.google.v1.'.sha1(strtolower(trim($query)));
    }

    public function isConfigured(): bool
    {
        return filled(config('services.google_maps.key'));
    }

    public function isCached(string $query): bool
    {
        $cached = Redis::get(static::cacheKey($query));

        return $cached !== null && $cached !== false;
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $query): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $cacheKey = static::cacheKey($query);

        $cached = Redis::get($cacheKey);
        if ($cached !== null && $cached !== false) {
            return $cached === self::NOT_FOUND ? null : json_decode($cached, true);
        }

        $result = $this->fetch($query);

        // A request failure (network error / non-2xx / an API status other
        // than OK or ZERO_RESULTS) is likely transient, so it's
        // deliberately left uncached: a genuine "no results" is cached as
        // not-found below, but a failure should be retried on the next
        // request rather than being stuck for a day.
        if ($result === false) {
            return null;
        }

        Redis::setex(
            $cacheKey,
            $result ? self::FOUND_TTL : self::NOT_FOUND_TTL,
            $result ? json_encode($result) : self::NOT_FOUND,
        );

        return $result;
    }

    /**
     * Returns null when Google resolved the query but found nothing, or
     * false when the request itself failed (network error, a non-2xx
     * response, or an API status other than OK/ZERO_RESULTS).
     *
     * @return array{lat: float, lng: float}|null|false
     */
    protected function fetch(string $query): array|null|false
    {
        try {
            $response = Http::get(self::ENDPOINT, [
                'address' => $query,
                'key' => config('services.google_maps.key'),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to reach the Google Geocoding API while geocoding a venue location.', [
                'query' => $query,
                'exception' => $e,
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('The Google Geocoding API returned an error response while geocoding a venue location.', [
                'query' => $query,
                'status' => $response->status(),
            ]);

            return false;
        }

        $status = $response->json('status');

        if ($status === 'ZERO_RESULTS') {
            return null;
        }

        if ($status !== 'OK') {
            Log::warning('The Google Geocoding API could not geocode a venue location.', [
                'query' => $query,
                'status' => $status,
            ]);

            return false;
        }

        $location = $response->json('results.0.geometry.location');
        if (! isset($location['lat'], $location['lng'])) {
            return null;
        }

        return ['lat' => (float) $location['lat'], 'lng' => (float) $location['lng']];
    }
}
