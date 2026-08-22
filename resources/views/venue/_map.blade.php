@if($venue->location)
<div
    id="venues-map"
    data-points-url="{{ route('venue.points', $venue->slug) }}"
    data-tile-url="{{ config('app.map_tile_url_template') }}"
    data-tile-attribution="{{ config('app.map_tile_attribution') }}"
    style="height: 300px;"
    class="mt-3"
></div>
<p id="map-status" class="text-muted small mt-2 mb-0" role="status" aria-live="polite"></p>
@endif
