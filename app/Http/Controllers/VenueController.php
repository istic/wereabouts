<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsVenueMapPoints;
use App\Service\Geocoding\NominatimGeocoder;
use App\Service\Google\GoogleClient;
use Illuminate\Contracts\View\View;

class VenueController extends Controller
{
    use BuildsVenueMapPoints;

    public function __construct(
        protected GoogleClient $googleSheet,
        protected NominatimGeocoder $geocoder,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $venues = $this->googleSheet->listVenues();

        return view('venue.index', compact('venues'));
    }

    /**
     * Display a single venue.
     */
    public function show(string $slug): View
    {
        $venue = $this->googleSheet->findVenueBySlug($slug);

        abort_if(! $venue, 404);

        $points = [];
        if ($venue->location) {
            $coordinates = $this->geocoder->geocode($this->geocodeQueryFor($venue));
            if ($coordinates) {
                $points[] = $this->venueMapPoint($venue, $coordinates);
            }
        }

        return view('venue.show', compact('venue', 'points'));
    }
}
