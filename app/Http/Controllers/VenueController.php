<?php

namespace App\Http\Controllers;

use App\Service\Google\GoogleClient;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function __construct(

        protected GoogleClient $googleSheet,

    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $venues =  $this->googleSheet->listVenues();
        $venues = $this->googleSheet->sortVenuesByName($venues);

        return view('venue.index', compact('venues'));
    }

    /**
     * Display the specified resource.
     */
    public function show($venueName)
    {
        $venue = $this->googleSheet->getVenueByName($venueName);

        if (!$venue) {
            abort(404, 'Venue not found');
        }

        return view('venue.show', compact('venue'));
    }
}
