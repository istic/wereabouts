<?php

namespace App\Http\Controllers\Concerns;

use App\Service\Google\Venue;

/**
 * Shared by every controller that plots venues on a Leaflet map, so the
 * single-venue and all-venues maps stay in lockstep on how a venue becomes
 * a geocoding query and a marker.
 */
trait BuildsVenueMapPoints
{
    protected function geocodeQueryFor(Venue $venue): string
    {
        return "{$venue->name}, {$venue->location}";
    }

    /**
     * @param  array{lat: float, lng: float}  $coordinates
     * @return array{name: string, url: string, open: bool, lat: float, lng: float}
     */
    protected function venueMapPoint(Venue $venue, array $coordinates): array
    {
        return [
            'name' => $venue->name,
            'url' => route('venue.show', $venue->slug),
            'open' => $venue->open,
            'lat' => $coordinates['lat'],
            'lng' => $coordinates['lng'],
        ];
    }
}
