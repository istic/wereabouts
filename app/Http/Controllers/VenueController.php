<?php

namespace App\Http\Controllers;

use App\Service\Google\GoogleClient;
use Illuminate\Contracts\View\View;

class VenueController extends Controller
{
    public function __construct(

        protected GoogleClient $googleSheet,

    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $venues = $this->googleSheet->listVenues();
        $venues = $this->googleSheet->sortVenuesByName($venues);

        return view('venue.index', compact('venues'));
    }
}
