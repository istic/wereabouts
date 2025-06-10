<?php

namespace App\Models;

use \Google\Service\Sheets;
use Illuminate\Support\Facades\Redis;

// use Illuminate\Database\Eloquent\Model;

class VenueSheet extends GoogleClient
{
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
        $range = 'Overnight Venue Site List!A:M'; // Adjust the range as needed
        $cacheKey = 'venue.list' . sha1($range);
        if (Redis::exists($cacheKey)) {
            $venue_list = Redis::get($cacheKey);
            // return json_decode($venue_list, true);
        }

        $sheet = $this->getSheet();
        if (!$sheet) {
            throw new \Exception('Google Sheets service not initialized.');
        }

        $result = $sheet->spreadsheets_values->get($this->sheetID, $range);
        Redis::set($cacheKey, json_encode($result->getValues()));
        Redis::expire($cacheKey, 3600); // Cache for 1 hour
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
            $venue['open'] = $venue_open; // Add 'open' status to each venue
            $venue['data'] = []; // Initialize 'data' as an empty array
            $venue['data']['capacity_count'] = (int) filter_var($venue['Capacity'], FILTER_SANITIZE_NUMBER_INT); // Ensure capacity count is an integer
            $venue['data']['public_transport_guess'] = filter_var($venue['Public Transport'], FILTER_VALIDATE_BOOL); // Convert to boolean
            $venue['data']['disabled_bathrooms_guess'] = filter_var($venue['Disabled bathrooms?'], FILTER_VALIDATE_BOOL); // Convert to boolean
            $venues[] = $venue;
        }
        return $venues;
    }
}
