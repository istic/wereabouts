<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsVenueMapPoints;
use App\Service\Geocoding\FallbackGeocoder;
use App\Service\Google\GoogleClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class MapController extends Controller
{
    use BuildsVenueMapPoints;

    public function __construct(
        protected GoogleClient $googleSheet,
        protected FallbackGeocoder $geocoder,
    ) {}

    /**
     * Display the all-venues map page. The venue points themselves are
     * fetched client-side from points(), so this renders immediately
     * regardless of how cold the geocoding cache is.
     */
    public function index(): View
    {
        return view('map.index');
    }

    /**
     * Geocode as many not-yet-cached venues as the configured cap allows
     * and return every venue's point as JSON. The page re-polls this
     * while any venues are still pending, so the map fills in as venues
     * are geocoded rather than needing a reload.
     */
    public function points(): JsonResponse
    {
        $venues = $this->googleSheet->listVenues();

        $points = [];
        $unmapped = 0;
        $pending = 0;
        $liveLookups = 0;
        $maxLiveLookups = config('app.map_max_live_geocodes_per_request');

        foreach ($venues as $venue) {
            if (! $venue->location) {
                $unmapped++;

                continue;
            }

            $query = $this->geocodeQueryFor($venue);
            $cached = $this->geocoder->isCached($query);

            // A cold cache would otherwise turn one page load into a many-
            // second wait behind Nominatim's 1 request/second usage policy;
            // once the cap is hit, leave the rest for a later visit, by
            // which point this batch will have warmed their cache entries.
            if (! $cached && $liveLookups >= $maxLiveLookups) {
                $pending++;

                continue;
            }
            if (! $cached) {
                $liveLookups++;
            }

            $coordinates = $this->geocoder->geocode($query);
            if (! $coordinates) {
                $unmapped++;

                continue;
            }

            $points[] = $this->venueMapPoint($venue, $coordinates);
        }

        return response()->json(compact('points', 'unmapped', 'pending'));
    }
}
