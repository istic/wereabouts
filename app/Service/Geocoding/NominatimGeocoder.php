<?php

namespace App\Service\Geocoding;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Turns a free-text location into coordinates via OpenStreetMap's public
 * Nominatim search API, so venues don't need a Google Maps API key (and
 * its billing) just to be plotted on a map.
 *
 * Nominatim's usage policy caps anonymous use at one request per second and
 * requires a way to identify the calling application, so results are cached
 * for a long time (an address's coordinates rarely change) and the live
 * lookup is throttled between calls.
 */
class NominatimGeocoder
{
    protected const ENDPOINT = 'https://nominatim.openstreetmap.org/search';

    /**
     * Sentinel cached value for a query Nominatim couldn't resolve, so a
     * bad/unresolvable address isn't retried on every single request.
     */
    protected const NOT_FOUND = '__not_found__';

    protected const FOUND_TTL = 60 * 60 * 24 * 30; // 30 days

    protected const NOT_FOUND_TTL = 60 * 60 * 24; // 1 day, in case it was transient

    /**
     * The Redis key the last live Nominatim request (by any process) was
     * made at, used to throttle across all of them - see throttle().
     */
    protected const THROTTLE_KEY = 'nominatim.throttle.last_request_at';

    /**
     * The Redis key a query's coordinates are cached under, versioned so
     * bumping it after a shape change only requires touching one place.
     * Includes the active country restriction, since a query cached under
     * one restriction isn't a valid result under a different (or no)
     * restriction.
     */
    public static function cacheKey(string $query): string
    {
        $countryCode = config('app.geocode_country_code') ?: 'unrestricted';

        return 'geocode.v1.'.$countryCode.'.'.sha1(strtolower(trim($query)));
    }

    /**
     * Whether $query already has a cached result (found or not-found),
     * i.e. whether looking it up would avoid a live Nominatim request.
     */
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
        $cacheKey = static::cacheKey($query);

        $cached = Redis::get($cacheKey);
        if ($cached !== null && $cached !== false) {
            return $cached === self::NOT_FOUND ? null : json_decode($cached, true);
        }

        $result = $this->fetch($query);

        // A request failure (network error / non-2xx) is likely transient,
        // so it's deliberately left uncached: a genuine "no results" is
        // cached as not-found below, but a failure should be retried on
        // the next request rather than being stuck for a day.
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
     * Returns null when Nominatim resolved the query but found nothing,
     * or false when the request itself failed (network error or a
     * non-2xx response).
     *
     * @return array{lat: float, lng: float}|null|false
     */
    protected function fetch(string $query): array|null|false
    {
        $this->throttle();

        try {
            $response = Http::withHeaders([
                // Nominatim's usage policy requires a way to identify the
                // application making requests.
                'User-Agent' => config('app.name').' geocoder (+'.config('app.url').')',
            ])->get(self::ENDPOINT, array_filter([
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 1,
                // Restricts (not just biases) results to this country, so
                // a free-text query without a country name can't match a
                // same-named place elsewhere in the world.
                'countrycodes' => config('app.geocode_country_code'),
            ]));
        } catch (Throwable $e) {
            Log::warning('Failed to reach Nominatim while geocoding a venue location.', [
                'query' => $query,
                'exception' => $e,
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('Nominatim returned an error response while geocoding a venue location.', [
                'query' => $query,
                'status' => $response->status(),
            ]);

            return false;
        }

        $result = $response->json()[0] ?? null;
        if (! isset($result['lat'], $result['lon'])) {
            return null;
        }

        return ['lat' => (float) $result['lat'], 'lng' => (float) $result['lon']];
    }

    /**
     * Nominatim's usage policy caps anonymous use at one request per
     * second, enforced here across every process/worker (not just
     * repeated calls within this instance) via a shared last-request
     * timestamp in Redis. This is best-effort, not a hard guarantee: two
     * processes can both pass the check within the same poll window, but
     * it closes the much larger gap of concurrent workers not
     * coordinating at all.
     */
    protected function throttle(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        while (true) {
            $lastRequestAt = Redis::get(self::THROTTLE_KEY);
            $elapsed = $lastRequestAt === null || $lastRequestAt === false
                ? null
                : microtime(true) - (float) $lastRequestAt;

            if ($elapsed === null || $elapsed >= 1.0) {
                break;
            }

            usleep(100_000);
        }

        Redis::set(self::THROTTLE_KEY, microtime(true));
    }
}
