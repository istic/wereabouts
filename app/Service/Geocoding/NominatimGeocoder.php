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

    protected bool $hasMadeLiveRequest = false;

    /**
     * The Redis key a query's coordinates are cached under, versioned so
     * bumping it after a shape change only requires touching one place.
     */
    public static function cacheKey(string $query): string
    {
        return 'geocode.v1.'.sha1(strtolower(trim($query)));
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
            ])->get(self::ENDPOINT, [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 1,
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to reach Nominatim while geocoding a venue location.', [
                'query' => $query,
                'exception' => $e,
            ]);

            return false;
        }

        $this->hasMadeLiveRequest = true;

        if (! $response->successful()) {
            Log::warning('Nominatim returned an error response while geocoding a venue location.', [
                'query' => $query,
                'status' => $response->status(),
            ]);

            return false;
        }

        $result = $response->json(0);
        if (! isset($result['lat'], $result['lon'])) {
            return null;
        }

        return ['lat' => (float) $result['lat'], 'lng' => (float) $result['lon']];
    }

    /**
     * Nominatim's usage policy caps anonymous use at one request per
     * second; sleep before every live request after the first one this
     * instance has made.
     */
    protected function throttle(): void
    {
        if ($this->hasMadeLiveRequest && ! app()->environment('testing')) {
            sleep(1);
        }
    }
}
