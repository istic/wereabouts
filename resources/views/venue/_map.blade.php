@if($venue->location)
<div class="ratio ratio-16x9 mt-3" id="Venue_Map_{{ $venue->slug }}">
    <iframe
        src="https://www.google.com/maps?q={{ urlencode($venue->name.' '.$venue->location) }}&output=embed"
        title="Map showing the location of {{ $venue->name }}"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        allowfullscreen></iframe>
</div>
@endif
