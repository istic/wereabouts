<?php

namespace App\Models;

use \Google\Service\Sheets;
use Illuminate\Support\Facades\Redis;

// use Illuminate\Database\Eloquent\Model;

class VenueSheet extends GoogleClient
{

    protected $sheetID;
    /**
     * VenueSheet constructor.
     */

    public function __construct()
    {
        parent::__construct();
        $this->sheetID = config('app.venue_sheet_id');
    }
    protected function getSheet()
    {


        $service = new \Google\Service\Sheets($this->client);

        return $service;
    }

    public function listVenues()
    {
        $cacheKey = 'venue.index';
        if (Redis::exists($cacheKey)) {
            $venue_list = Redis::get($cacheKey);
            return json_decode($venue_list, true);
        }

        $sheet = $this->getSheet();
        if (!$sheet) {
            throw new \Exception('Google Sheets service not initialized.');
        }

        $range = 'Overnight Venue Site List!A:M'; // Adjust the range as needed
        $result = $sheet->spreadsheets_values->get($this->sheetID, $range);
        $sites = $result->getValues();
        $headings = array_shift($sites); // Remove the first row as headings
        $headings[0] = 'Venue Name'; // Rename the first heading to 'Venue Name'
        array_shift($sites); // Remove the second row as it is not needed

        $venues = [];
        $venue_open = true;
        foreach ($sites as $site) {
            if (count($site) < 1 || !$site[0]) {
                continue; // Skip if the first column (Venue Name) is empty
            }
            $site = array_map('trim', $site); // Trim whitespace from all columns
            if ($site[0] === 'Closed Venues') {
                $venue_open = false; // If the first column is 'Closed', set open status to false
                continue; // Skip this row as it indicates closed venues
            }
            // dump($site); // Debugging line to inspect each site data
            $venue = [];
            foreach ($headings as $index => $heading) {
                $venue[$heading] = isset($site[$index]) ? $site[$index] : null;
            }
            $venue['data'] = []; // Initialize 'data' as an empty array
            $venue['data']['open'] = $venue_open; // Add 'open' status to each venue
            $venue['data']['capacity_count'] = (int) filter_var($venue['Capacity'], FILTER_SANITIZE_NUMBER_INT); // Ensure capacity count is an integer
            $venue['data']['public_transport_guess'] = filter_var($venue['Public Transport'], FILTER_VALIDATE_BOOL); // Convert to boolean
            $venue['data']['disabled_bathrooms_guess'] = filter_var($venue['Disabled bathrooms?'], FILTER_VALIDATE_BOOL); // Convert to boolean
            $venues[] = $venue;
        }
        $venues = $this->sortVenuesByName($venues); // Sort venues by name
        Redis::set($cacheKey, json_encode($venues));
        Redis::expire($cacheKey, 3600); // Cache for 1 hour
        return $venues;
    }

    public function sortVenuesByName($venues)
    {
        usort($venues, function ($a, $b) {
            return strcmp($a['Venue Name'], $b['Venue Name']);
        });
        return $venues;
    }
}
