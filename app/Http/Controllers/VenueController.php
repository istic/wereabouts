<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsVenueMapPoints;
use App\Service\Geocoding\FallbackGeocoder;
use App\Service\Google\GoogleClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class VenueController extends Controller
{
    use BuildsVenueMapPoints;

    public function __construct(
        protected GoogleClient $googleSheet,
        protected FallbackGeocoder $geocoder,
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
     * Display a single venue. Its map point is fetched client-side from
     * points(), so this renders immediately regardless of how cold the
     * geocoding cache is.
     */
    public function show(string $slug): View
    {
        $venue = $this->googleSheet->findVenueBySlug($slug);

        abort_if(! $venue, 404);

        return view('venue.show', compact('venue'));
    }

    /**
     * Geocode this venue and return its point as JSON.
     */
    public function points(string $slug): JsonResponse
    {
        $venue = $this->googleSheet->findVenueBySlug($slug);

        abort_if(! $venue, 404);

        $points = [];
        $unmapped = 0;

        if ($venue->location) {
            $coordinates = $this->geocoder->geocode($this->geocodeQueryFor($venue));

            if ($coordinates) {
                $points[] = $this->venueMapPoint($venue, $coordinates);
            } else {
                $unmapped = 1;
            }
        }

        return response()->json(['points' => $points, 'unmapped' => $unmapped, 'pending' => 0]);
    }
}
