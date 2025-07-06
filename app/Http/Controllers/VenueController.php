<?php

namespace App\Http\Controllers;

use App\Models\VenueSheet;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $venueSheet = new VenueSheet();
        $venues =  $venueSheet->listVenues();
        $venues = $venueSheet->sortVenuesByName($venues);

        return view('venue.index', compact('venues'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(VenueSheet $venueSheet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VenueSheet $venueSheet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VenueSheet $venueSheet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VenueSheet $venueSheet)
    {
        //
    }
}
