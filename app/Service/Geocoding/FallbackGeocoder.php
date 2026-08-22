<?php

namespace App\Service\Geocoding;

/**
 * Tries Nominatim first, falling back to Google's Geocoding API (when
 * configured) for whatever Nominatim can't resolve. This is what
 * controllers actually depend on, rather than NominatimGeocoder directly.
 */
class FallbackGeocoder
{
    public function __construct(
        protected NominatimGeocoder $nominatim,
        protected GoogleGeocoder $google,
    ) {}

    /**
     * Whether looking $query up would avoid a live Nominatim request -
     * Nominatim's rate-limited "sleep between live calls" throttle is the
     * only leg of this chain a request needs to bound, since the Google
     * fallback isn't rate-limited the same way. A query Nominatim has
     * already given up on (cached not-found) still reports as "cached"
     * here: geocode() below won't touch Nominatim's live path for it
     * again, only Google's.
     */
    public function isCached(string $query): bool
    {
        return $this->nominatim->isCached($query);
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $query): ?array
    {
        $coordinates = $this->nominatim->geocode($query);

        if ($coordinates) {
            return $coordinates;
        }

        return $this->google->geocode($query);
    }
}
