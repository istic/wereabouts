<?php

namespace App\Http\Controllers;

use App\Service\Geocoding\NominatimGeocoder;
use App\Service\Google\GoogleClient;
use Illuminate\Contracts\View\View;

class MapController extends Controller
{
    public function __construct(
        protected GoogleClient $googleSheet,
        protected NominatimGeocoder $geocoder,
    ) {}

    /**
     * Display all venues plotted on a single map.
     */
    public function index(): View
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

            $query = "{$venue->name}, {$venue->location}";
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

            $points[] = [
                'name' => $venue->name,
                'url' => route('venue.show', $venue->slug),
                'open' => $venue->open,
                'lat' => $coordinates['lat'],
                'lng' => $coordinates['lng'],
            ];
        }

        return view('map.index', compact('points', 'unmapped', 'pending'));
    }
}
