<div class="row">
    <label for="Venue_Status_{{ $venue->slug }}" class="col-sm-5 col-form-label">Status</label>
    <div class="col-sm-5">
      <span id="Venue_Status_{{ $venue->slug }}" class="badge {{ $venue->open ? 'bg-success' : 'bg-secondary' }}">{{ $venue->open ? 'Open' : 'Closed' }}</span>
    </div>
</div>
@if($venue->website)
<div class="row">
    <label for="Venue_Website_{{ $venue->slug }}" class="col-sm-5 col-form-label">Website</label>
    <div class="col-sm-5">
      <span id="Venue_Website_{{ $venue->slug }}">{!! auto_link($venue->website, popup: true) !!}</span>
    </div>
</div>
@endif
<div class="row">
    <label for="Venue_Location_{{ $venue->slug }}" class="col-sm-5 col-form-label">Location</label>
    <div class="col-sm-5">
      <a id="Venue_Location_{{ $venue->slug }}" target="_blank" rel="noopener noreferrer" href="https://maps.google.com/?q={{ urlencode($venue->name . " " . $venue->location) }}">{{ $venue->location }}</a>
    </div>
</div>
<div class="row">
    <label for="Venue_Capacity_{{ $venue->slug }}" class="col-sm-5 col-form-label">Capacity</label>
    <div class="col-sm-5">
      <span id="Venue_Capacity_{{ $venue->slug }}">{{ $venue->capacity }}</span>
    </div>
</div>
<div class="row">
    <label for="Venue_Types_of_Spaces_{{ $venue->slug }}" class="col-sm-5 col-form-label">Types of Spaces</label>
    <div class="col-sm-5">
      <span id="Venue_Types_of_Spaces_{{ $venue->slug }}">{{ $venue->typesOfSpaces }}</span>
    </div>
</div>
<div class="row">
    <label for="Venue_Public_Transport_{{ $venue->slug }}" class="col-sm-5 col-form-label">Public Transport</label>
    <div class="col-sm-5">
      <span id="Venue_Public_Transport_{{ $venue->slug }}">{{ $venue->publicTransport }}</span>
    </div>
</div>
<div class="row">
    <label for="Venue_Step_free_access_{{ $venue->slug }}" class="col-sm-5 col-form-label">Step free access</label>
    <div class="col-sm-5">
      <span id="Venue_Step_free_access_{{ $venue->slug }}">{{ $venue->stepFreeAccess }}</span>
    </div>
</div>
<div class="row">
    <label for="Venue_Disabled_bathrooms_{{ $venue->slug }}" class="col-sm-5 col-form-label">Disabled bathrooms?</label>
    <div class="col-sm-5">
      <span id="Venue_Disabled_bathrooms_{{ $venue->slug }}">{{ $venue->disabledBathrooms }}</span>
    </div>
</div>
<div class="row">
    <label for="Venue_Internet_{{ $venue->slug }}" class="col-sm-5 col-form-label">Internet?</label>
    <div class="col-sm-5">
      <span id="Venue_Internet_{{ $venue->slug }}">{{ $venue->internet }}</span>
    </div>
</div>
<div class="row">
    <label for="Venue_Kitchen_{{ $venue->slug }}" class="col-sm-5 col-form-label">Kitchen</label>
    <div class="col-sm-5">
      <span id="Venue_Kitchen_{{ $venue->slug }}">{{ $venue->kitchen }}</span>
    </div>
</div>
<div class="row">
    <label for="Venue_Issues_{{ $venue->slug }}" class="col-sm-5 col-form-label">Issues</label>
    <div class="col-sm-5">
      <span id="Venue_Issues_{{ $venue->slug }}">{{ $venue->issues }}</span>
    </div>
</div>
<div class="row">
    <label for="Venue_Further_description_of_indoor_spaces_{{ $venue->slug }}" class="col-sm-5 col-form-label">Further description of indoor spaces</label>
    <div class="col-sm-5">
      <span id="Venue_Further_description_of_indoor_spaces_{{ $venue->slug }}">{{ $venue->furtherDescription }}</span>
    </div>
</div>
<div class="row">
    <label for="Venue_Aspects_{{ $venue->slug }}" class="col-sm-5 col-form-label">Aspects</label>
    <div class="col-sm-5">
      <span id="Venue_Aspects_{{ $venue->slug }}">{!! auto_link($venue->aspects) !!}</span>
    </div>
</div>
<div class="row">
    <label for="Venue_Price_data_{{ $venue->slug }}" class="col-sm-5 col-form-label">Price data (cost + data of recorded cost)</label>
    <div class="col-sm-5">
      <span id="Venue_Price_data_{{ $venue->slug }}">{{ $venue->priceData }}</span>
    </div>
</div>
