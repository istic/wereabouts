@if(count($points))
<div id="venues-map" data-points='@json($points)' style="height: 300px;" class="mt-3"></div>
@elseif($venue->location)
<p class="text-muted small mt-3 mb-0">This venue could not be placed on the map automatically.</p>
@endif
