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
     * Includes the same fields the venue listing page's client-side
     * filters use (see venue/index.blade.php's .venue-card data
     * attributes), so the all-venues map can offer identical filtering.
     *
     * @param  array{lat: float, lng: float}  $coordinates
     * @return array{name: string, url: string, open: bool, lat: float, lng: float, location: ?string, capacity: int, publicTransport: bool, disabledBathrooms: bool, stepFree: bool}
     */
    protected function venueMapPoint(Venue $venue, array $coordinates): array
    {
        return [
            'name' => $venue->name,
            'url' => route('venue.show', $venue->slug),
            'open' => $venue->open,
            'lat' => $coordinates['lat'],
            'lng' => $coordinates['lng'],
            'location' => $venue->location,
            'capacity' => $venue->capacityCount,
            'publicTransport' => venue_sheet_flag($venue->publicTransport),
            'disabledBathrooms' => venue_sheet_flag($venue->disabledBathrooms),
            'stepFree' => venue_sheet_flag($venue->stepFreeAccess, ['y', 'all']),
        ];
    }

    /**
     * @return array{name: string, url: string, reason: string}
     */
    protected function unmappedVenue(Venue $venue, string $reason): array
    {
        return [
            'name' => $venue->name,
            'url' => route('venue.show', $venue->slug),
            'reason' => $reason,
        ];
    }
}
